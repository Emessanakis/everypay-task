<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Repository;

use Lefteris\EverypayTask\Infrastructure\DatabaseConnection;
use Lefteris\EverypayTask\Model\Merchant;

class MerchantRepository implements MerchantRepositoryInterface
{
    public function __construct(
        private readonly DatabaseConnection $db,
    ) {}

    public function findByApiKey(string $apiKey): ?Merchant
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM merchants WHERE api_key = :api_key LIMIT 1'
        );
        $stmt->execute([':api_key' => $apiKey]);

        $row = $stmt->fetch();

        return $row !== false ? Merchant::fromRow($row) : null;
    }

    public function findById(string $id): ?Merchant
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM merchants WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row !== false ? Merchant::fromRow($row) : null;
    }
}
