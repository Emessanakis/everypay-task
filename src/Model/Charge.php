<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Model;

class Charge
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $merchantId,
        public readonly int     $amount,
        public readonly string  $currency,
        public readonly string  $status,
        public readonly ?string $transactionId,
        public readonly string  $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:            $row['id'],
            merchantId:    $row['merchant_id'],
            amount:        (int) $row['amount'],
            currency:      $row['currency'],
            status:        $row['status'],
            transactionId: $row['transaction_id'] ?? null,
            createdAt:     $row['created_at'],
        );
    }
}
