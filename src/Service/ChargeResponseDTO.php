<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Service;

class ChargeResponseDTO
{
    public function __construct(
        public readonly string  $chargeId,
        public readonly string  $status,
        public readonly ?string $transactionId,
        public readonly int     $amount,
        public readonly string  $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'charge_id'      => $this->chargeId,
            'status'         => $this->status,
            'transaction_id' => $this->transactionId,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
        ];
    }
}
