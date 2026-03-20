<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Tests\Unit;

use Lefteris\EverypayTask\Infrastructure\ApiKeyAuthenticator;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Middleware\AuthMiddleware;
use Lefteris\EverypayTask\Model\Merchant;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private ApiKeyAuthenticator&MockInterface $authenticator;
    private AuthMiddleware                    $middleware;

    protected function setUp(): void
    {
        $this->authenticator = Mockery::mock(ApiKeyAuthenticator::class);
        $this->middleware    = new AuthMiddleware($this->authenticator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testReturns401WhenApiKeyHeaderIsMissing(): void
    {
        $request  = Mockery::mock(Request::class);
        $response = new SpyAuthResponse();

        $request->shouldReceive('getHeader')->with('X-API-Key')->andReturn(null);

        $nextCalled = false;
        ($this->middleware)($request, $response, function () use (&$nextCalled) {
            $nextCalled = true;
        });

        self::assertSame(401, $response->capturedStatus);
        self::assertStringContainsString('Missing X-API-Key', $response->capturedBody);
        self::assertFalse($nextCalled);
    }

    public function testReturns401WhenApiKeyIsInvalid(): void
    {
        $request  = Mockery::mock(Request::class);
        $response = new SpyAuthResponse();

        $request->shouldReceive('getHeader')->with('X-API-Key')->andReturn('bad-key');

        $this->authenticator
            ->shouldReceive('authenticate')
            ->with('bad-key')
            ->andReturn(null);

        $nextCalled = false;
        ($this->middleware)($request, $response, function () use (&$nextCalled) {
            $nextCalled = true;
        });

        self::assertSame(401, $response->capturedStatus);
        self::assertStringContainsString('Invalid API key', $response->capturedBody);
        self::assertFalse($nextCalled);
    }

    public function testSetsMerchantAttributeAndCallsNextOnValidKey(): void
    {
        $merchant = new Merchant('id', 'Test', 'test@test.com', 'valid-key', 'merchant', 'fakeStripe', '2024-01-01');

        $request  = Mockery::mock(Request::class);
        $response = new SpyAuthResponse();

        $request->shouldReceive('getHeader')->with('X-API-Key')->andReturn('valid-key');
        $request->shouldReceive('setAttribute')->with('merchant', $merchant)->once();

        $this->authenticator
            ->shouldReceive('authenticate')
            ->with('valid-key')
            ->andReturn($merchant);

        $nextCalled = false;
        ($this->middleware)($request, $response, function () use (&$nextCalled) {
            $nextCalled = true;
        });

        self::assertTrue($nextCalled);
        self::assertSame(0, $response->capturedStatus); // no response written
    }
}

class SpyAuthResponse extends Response
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

    public function send(): void {}
}
