<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

use PDO;
use PDOException;
use RuntimeException;

class DatabaseConnection
{
    private PDO $pdo;

    public function __construct(
        string $host,
        string $dbname,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
    ) {
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
