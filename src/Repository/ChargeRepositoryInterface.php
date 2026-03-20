<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Repository;

use Lefteris\EverypayTask\Model\Charge;

interface ChargeRepositoryInterface
{
    public function save(Charge $charge): void;

    /**
     * Return all charges.
     *
     * @return Charge[]
     */
    public function findAll(): array;

    /**
     * Return all charges for a merchant.
     *
     * @return Charge[]
     */
    public function findByMerchant(string $merchantId): array;

    /**
     * Return all charges within the given date/time range.
     *
     * @return Charge[]
     */
    public function findByDateRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array;

    /**
     * Return all charges for a merchant within the given date/time range.
     *
     * @return Charge[]
     */
    public function findByMerchantAndDateRange(
        string             $merchantId,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array;
}
