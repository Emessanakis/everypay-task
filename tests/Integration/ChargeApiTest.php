<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Tests\Integration;

use Lefteris\EverypayTask\Controller\ChargeController;
use Lefteris\EverypayTask\Domain\Entity\Merchant;
use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Service\ChargeResponseDTO;
use Lefteris\EverypayTask\Service\ChargeService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration-style tests wiring ChargeController with a mocked ChargeService.
 *
 * Covers the full validation → service call → HTTP response mapping without
 * touching the database or any external system.
 */
class ChargeApiTest extends TestCase
{
    private ChargeService&MockInterface $chargeService;
    private ChargeController            $controller;

    protected function setUp(): void
    {
        $this->chargeService = Mockery::mock(ChargeService::class);
        $this->controller    = new ChargeController($this->chargeService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Build a Request mock that returns the given body / api key. */
    private function makeRequest(string $body, string $apiKey = 'test-api-key'): Request&MockInterface
    {
        $merchant = new Merchant('merchant-id', 'Test Merchant', 'test@example.com', $apiKey, 'stripe');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getAttribute')->with('merchant')->andReturn($merchant);
        $request->shouldReceive('getBody')->andReturn($body);
        $request->shouldReceive('getHeader')->with('X-API-Key')->andReturn($apiKey);

        return $request;
    }

    private function captureResponse(): SpyResponse
    {
        return new SpyResponse();
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    public function testReturns403WhenCallerIsAdmin(): void
    {
        $admin   = new Merchant('admin-id', 'Admin', 'admin@example.com', 'admin-key', 'stripe', true);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getAttribute')->with('merchant')->andReturn($admin);

        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(403, $response->capturedStatus);
        self::assertStringContainsString('Forbidden', $response->capturedBody);
    }

    public function testReturns400ForInvalidJsonBody(): void
    {
        $request  = $this->makeRequest('not-valid-json');
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(400, $response->capturedStatus);
        self::assertStringContainsString('Invalid JSON', $response->capturedBody);
    }

    public function testReturns422WhenAmountIsMissing(): void
    {
        $request  = $this->makeRequest(json_encode(['currency' => 'EUR']));
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(422, $response->capturedStatus);
        self::assertStringContainsString('amount', $response->capturedBody);
    }

    public function testReturns422WhenAmountIsNotAnInteger(): void
    {
        $request  = $this->makeRequest(json_encode(['amount' => '1000']));
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(422, $response->capturedStatus);
    }

    public function testReturns201OnSuccessfulCharge(): void
    {
        $body = json_encode([
            'amount'       => 1000,
            'currency'     => 'EUR',
            'card_number'  => '4111111111111111',
            'cvv'          => '123',
            'expiry_month' => 12,
            'expiry_year'  => 2027,
        ]);

        $this->chargeService
            ->shouldReceive('charge')
            ->once()
            ->andReturn(new ChargeResponseDTO(
                chargeId:      'charge-uuid-001',
                status:        'succeeded',
                transactionId: 'stripe_txn_abc',
                amount:        1000,
                currency:      'EUR',
            ));

        $request  = $this->makeRequest($body);
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(201, $response->capturedStatus);

        $decoded = json_decode($response->capturedBody, true);
        self::assertSame('charge-uuid-001', $decoded['charge_id']);
        self::assertSame('succeeded', $decoded['status']);
        self::assertSame('stripe_txn_abc', $decoded['transaction_id']);
        self::assertSame(1000, $decoded['amount']);
        self::assertSame('EUR', $decoded['currency']);
    }

    public function testReturns404WhenMerchantNotFound(): void
    {
        $body = json_encode(['amount' => 500, 'currency' => 'EUR']);

        $this->chargeService
            ->shouldReceive('charge')
            ->andThrow(new NotFoundException('Merchant not found'));

        $request  = $this->makeRequest($body);
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(404, $response->capturedStatus);
        self::assertStringContainsString('Merchant not found', $response->capturedBody);
    }

    public function testReturns402WhenPaymentFails(): void
    {
        $body = json_encode(['amount' => 500, 'currency' => 'EUR']);

        $this->chargeService
            ->shouldReceive('charge')
            ->andThrow(new PaymentFailedException('card declined'));

        $request  = $this->makeRequest($body);
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(402, $response->capturedStatus);
        self::assertStringContainsString('card declined', $response->capturedBody);
    }

    public function testReturns500OnUnexpectedException(): void
    {
        $body = json_encode(['amount' => 500]);

        $this->chargeService
            ->shouldReceive('charge')
            ->andThrow(new \RuntimeException('DB connection lost'));

        $request  = $this->makeRequest($body);
        $response = $this->captureResponse();

        $this->controller->handle($request, $response);

        self::assertSame(500, $response->capturedStatus);
        self::assertStringContainsString('Internal server error', $response->capturedBody);
    }
}

/**
 * A test double for Response that captures status code and body
 * without emitting any real HTTP output.
 */
class SpyResponse extends Response
{
    public int    $capturedStatus = 0;
    public string $capturedBody   = '';

    public function setStatusCode(int $code): static
    {
        $this->capturedStatus = $code;
        return parent::setStatusCode($code);
    }

    public function setBody(string $body): static
    {
        $this->capturedBody = $body;
        return parent::setBody($body);
    }

    public function send(): void
    {
        // Suppress real HTTP output during tests.
    }
}
