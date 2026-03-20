<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Provider;

use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Service\ChargeRequestDTO;
use Ramsey\Uuid\Uuid;

class FakeStripeProvider implements PaymentProviderInterface
{
    public function charge(ChargeRequestDTO $dto): string
    {
        $this->validate($dto);

        // Simulate a ~10 % decline rate so callers can handle failures.
        if (random_int(1, 10) === 1) {
            throw new PaymentFailedException('fakeStripe: card was declined');
        }

        return 'stripe_txn_' . Uuid::uuid4()->toString();
    }

    private function validate(ChargeRequestDTO $dto): void
    {
        if (empty($dto->amount) || empty($dto->currency)) {
            throw new PaymentFailedException(
                'fakeStripe: amount and currency are required'
            );
        }

        if (
            empty($dto->cardNumber)
            || empty($dto->cvv)
            || empty($dto->expiryMonth)
            || empty($dto->expiryYear)
        ) {
            throw new PaymentFailedException(
                'fakeStripe: card_number, cvv, expiry_month and expiry_year are required'
            );
        }
    }
}
