<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Domain\Entity;

final class Money
{
    public function __construct(
        public readonly int    $amount,   // stored in minor units (cents)
        public readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be a non-negative integer');
        }
    }

    public function format(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->amount / 100);
    }
}
