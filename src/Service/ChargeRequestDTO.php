<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Service;

class ChargeRequestDTO
{
    public function __construct(
        public readonly string  $apiKey,
        public readonly int     $amount,
        public readonly string  $currency,
        // fakeStripe fields
        public readonly ?string $cardNumber   = null,
        public readonly ?string $cvv          = null,
        public readonly ?int    $expiryMonth  = null,
        public readonly ?int    $expiryYear   = null,
        // fakePaypal fields
        public readonly ?string $email        = null,
        public readonly ?string $password     = null,
    ) {}
}
