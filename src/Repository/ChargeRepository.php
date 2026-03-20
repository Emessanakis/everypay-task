<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Repository;

use Lefteris\EverypayTask\Infrastructure\DatabaseConnection;
use Lefteris\EverypayTask\Model\Charge;

class ChargeRepository implements ChargeRepositoryInterface
{
    public function __construct(
        private readonly DatabaseConnection $db,
    ) {}

    public function save(Charge $charge): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO charges
                (id, merchant_id, amount, currency, status, transaction_id, created_at)
             VALUES
                (:id, :merchant_id, :amount, :currency, :status, :transaction_id, :created_at)'
        );

        $stmt->execute([
            ':id'             => $charge->id,
            ':merchant_id'    => $charge->merchantId,
            ':amount'         => $charge->amount,
            ':currency'       => $charge->currency,
            ':status'         => $charge->status,
            ':transaction_id' => $charge->transactionId,
            ':created_at'     => $charge->createdAt,
        ]);
    }

    public function findAll(): array
    {
        $stmt = $this->db->getPdo()->query(
            'SELECT * FROM charges ORDER BY created_at DESC'
        );

        return array_map(
            static fn(array $row) => Charge::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function findByMerchant(string $merchantId): array
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM charges WHERE merchant_id = :merchant_id ORDER BY created_at DESC'
        );

        $stmt->execute([':merchant_id' => $merchantId]);

        return array_map(
            static fn(array $row) => Charge::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function findByDateRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT *
               FROM charges
              WHERE created_at BETWEEN :from AND :to
              ORDER BY created_at DESC'
        );

        $stmt->execute([
            ':from' => $from->format('Y-m-d H:i:s'),
            ':to'   => $to->format('Y-m-d H:i:s'),
        ]);

        return array_map(
            static fn(array $row) => Charge::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function findByMerchantAndDateRange(
        string             $merchantId,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT *
               FROM charges
              WHERE merchant_id = :merchant_id
                AND created_at BETWEEN :from AND :to
              ORDER BY created_at DESC'
        );

        $stmt->execute([
            ':merchant_id' => $merchantId,
            ':from'        => $from->format('Y-m-d H:i:s'),
            ':to'          => $to->format('Y-m-d H:i:s'),
        ]);

        return array_map(
            static fn(array $row) => Charge::fromRow($row),
            $stmt->fetchAll()
        );
    }
}
