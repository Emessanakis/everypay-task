<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Tests\Unit;

use Lefteris\EverypayTask\Domain\Enum\ChargeStatus;
use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Model\Charge;
use Lefteris\EverypayTask\Model\Merchant;
use Lefteris\EverypayTask\Provider\PaymentProviderFactory;
use Lefteris\EverypayTask\Provider\PaymentProviderInterface;
use Lefteris\EverypayTask\Repository\ChargeRepositoryInterface;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;
use Lefteris\EverypayTask\Service\ChargeRequestDTO;
use Lefteris\EverypayTask\Service\ChargeResponseDTO;
use Lefteris\EverypayTask\Service\ChargeService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ChargePaymentServiceTest extends TestCase
{
    private MerchantRepositoryInterface&MockInterface $merchantRepository;
    private ChargeRepositoryInterface&MockInterface   $chargeRepository;
    private PaymentProviderFactory&MockInterface      $providerFactory;
    private PaymentProviderInterface&MockInterface    $provider;
    private ChargeService                             $service;

    protected function setUp(): void
    {
        $this->merchantRepository = Mockery::mock(MerchantRepositoryInterface::class);
        $this->chargeRepository   = Mockery::mock(ChargeRepositoryInterface::class);
        $this->providerFactory    = Mockery::mock(PaymentProviderFactory::class);
        $this->provider           = Mockery::mock(PaymentProviderInterface::class);

        $this->service = new ChargeService(
            $this->merchantRepository,
            $this->chargeRepository,
            $this->providerFactory,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeMerchant(): Merchant
    {
        return new Merchant(
            id:          'merchant-uuid-123',
            name:        'Test Merchant',
            email:       'merchant@example.com',
            apiKey:      'test-api-key',
            role:        'merchant',
            pspProvider: 'fakeStripe',
            createdAt:   '2024-01-01 00:00:00',
        );
    }

    private function makeDto(array $overrides = []): ChargeRequestDTO
    {
        return new ChargeRequestDTO(
            apiKey:      $overrides['apiKey']      ?? 'test-api-key',
            amount:      $overrides['amount']      ?? 1000,
            currency:    $overrides['currency']    ?? 'EUR',
            cardNumber:  $overrides['cardNumber']  ?? '4111111111111111',
            cvv:         $overrides['cvv']         ?? '123',
            expiryMonth: $overrides['expiryMonth'] ?? 12,
            expiryYear:  $overrides['expiryYear']  ?? 2027,
        );
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    public function testChargeThrowsNotFoundExceptionWhenMerchantDoesNotExist(): void
    {
        $dto = $this->makeDto();

        $this->merchantRepository
            ->shouldReceive('findByApiKey')
            ->with($dto->apiKey)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Merchant not found');

        $this->service->charge($dto);
    }

    public function testChargeSucceedsAndPersistsCharge(): void
    {
        $merchant = $this->makeMerchant();
        $dto      = $this->makeDto();

        $this->merchantRepository
            ->shouldReceive('findByApiKey')
            ->with($dto->apiKey)
            ->andReturn($merchant);

        $this->providerFactory
            ->shouldReceive('make')
            ->with($merchant)
            ->andReturn($this->provider);

        $transactionId = 'stripe_txn_abc123';

        $this->provider
            ->shouldReceive('charge')
            ->with($dto)
            ->andReturn($transactionId);

        $this->chargeRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn(Charge $c) =>
                $c->merchantId    === $merchant->id
                && $c->amount     === $dto->amount
                && $c->currency   === $dto->currency
                && $c->status     === ChargeStatus::Succeeded->value
                && $c->transactionId === $transactionId
            ));

        $result = $this->service->charge($dto);

        self::assertInstanceOf(ChargeResponseDTO::class, $result);
        self::assertSame(ChargeStatus::Succeeded->value, $result->status);
        self::assertSame($transactionId, $result->transactionId);
        self::assertSame($dto->amount, $result->amount);
        self::assertSame($dto->currency, $result->currency);
        self::assertNotEmpty($result->chargeId);
    }

    public function testChargeStoresFailedChargeAndRethrowsPaymentFailedException(): void
    {
        $merchant = $this->makeMerchant();
        $dto      = $this->makeDto();

        $this->merchantRepository
            ->shouldReceive('findByApiKey')
            ->with($dto->apiKey)
            ->andReturn($merchant);

        $this->providerFactory
            ->shouldReceive('make')
            ->with($merchant)
            ->andReturn($this->provider);

        $this->provider
            ->shouldReceive('charge')
            ->with($dto)
            ->andThrow(new PaymentFailedException('card declined'));

        $this->chargeRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn(Charge $c) =>
                $c->status         === ChargeStatus::Failed->value
                && $c->transactionId === null
            ));

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('card declined');

        $this->service->charge($dto);
    }

    public function testChargeReturnsDtoWithCorrectAmountAndCurrency(): void
    {
        $merchant = $this->makeMerchant();
        $dto      = $this->makeDto(['amount' => 5000, 'currency' => 'USD']);

        $this->merchantRepository
            ->shouldReceive('findByApiKey')
            ->andReturn($merchant);

        $this->providerFactory
            ->shouldReceive('make')
            ->andReturn($this->provider);

        $this->provider
            ->shouldReceive('charge')
            ->andReturn('stripe_txn_xyz');

        $this->chargeRepository
            ->shouldReceive('save')
            ->once();

        $result = $this->service->charge($dto);

        self::assertSame(5000, $result->amount);
        self::assertSame('USD', $result->currency);
    }
}
