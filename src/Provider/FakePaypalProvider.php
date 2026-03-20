<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Provider;

use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Service\ChargeRequestDTO;
use Ramsey\Uuid\Uuid;

class FakePaypalProvider implements PaymentProviderInterface
{
    public function charge(ChargeRequestDTO $dto): string
    {
        $this->validate($dto);

        // Simulate a ~10 % rejection rate.
        if (random_int(1, 10) === 1) {
            throw new PaymentFailedException('fakePaypal: transaction was rejected');
        }

        return 'paypal_txn_' . Uuid::uuid4()->toString();
    }

    private function validate(ChargeRequestDTO $dto): void
    {
        if (empty($dto->amount) || empty($dto->currency)) {
            throw new PaymentFailedException(
                'fakePaypal: amount and currency are required'
            );
        }

        if (empty($dto->email) || empty($dto->password)) {
            throw new PaymentFailedException(
                'fakePaypal: email and password are required'
            );
        }
    }
}
