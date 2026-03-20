<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Provider;

use Lefteris\EverypayTask\Model\Merchant;

class PaymentProviderFactory
{
    public function make(Merchant $merchant): PaymentProviderInterface
    {
        return match ($merchant->pspProvider) {
            'fakeStripe' => new FakeStripeProvider(),
            'fakePaypal' => new FakePaypalProvider(),
            default      => throw new \InvalidArgumentException(
                "Unsupported PSP provider: {$merchant->pspProvider}"
            ),
        };
    }
}
