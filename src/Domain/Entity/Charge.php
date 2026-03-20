<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Domain\Entity;

use Lefteris\EverypayTask\Domain\Enum\ChargeStatus;

final class Charge
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $merchantId,
        public readonly Money              $money,
        public readonly ChargeStatus       $status,
        public readonly ?string            $transactionId,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
