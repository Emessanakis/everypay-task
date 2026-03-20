<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Tests\Unit;

use Lefteris\EverypayTask\Model\Merchant;
use Lefteris\EverypayTask\Provider\FakePaypalProvider;
use Lefteris\EverypayTask\Provider\FakeStripeProvider;
use Lefteris\EverypayTask\Provider\PaymentProviderFactory;
use PHPUnit\Framework\TestCase;

class PaymentProviderFactoryTest extends TestCase
{
    private PaymentProviderFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new PaymentProviderFactory();
    }

    private function makeMerchant(string $pspProvider): Merchant
    {
        return new Merchant('id', 'Test', 'test@test.com', 'key', 'merchant', $pspProvider, '2024-01-01');
    }

    public function testMakeReturnsFakeStripeProvider(): void
    {
        $provider = $this->factory->make($this->makeMerchant('fakeStripe'));

        self::assertInstanceOf(FakeStripeProvider::class, $provider);
    }

    public function testMakeReturnsFakePaypalProvider(): void
    {
        $provider = $this->factory->make($this->makeMerchant('fakePaypal'));

        self::assertInstanceOf(FakePaypalProvider::class, $provider);
    }

    public function testMakeThrowsForUnknownProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported PSP provider: unknown');

        $this->factory->make($this->makeMerchant('unknown'));
    }
}
