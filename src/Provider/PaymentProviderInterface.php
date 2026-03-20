<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Provider;

use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Service\ChargeRequestDTO;

interface PaymentProviderInterface
{
    /**
     * Attempt to charge the payment method described by the DTO.
     *
     * @return string The provider-issued transaction ID on success.
     * @throws PaymentFailedException When the charge is declined or invalid.
     */
    public function charge(ChargeRequestDTO $dto): string;
}
