<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Model;

class Merchant
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $apiKey,
        public readonly string  $role,
        public readonly ?string $pspProvider,
        public readonly string  $createdAt,
    ) {}

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:          $row['id'],
            name:        $row['name'],
            email:       $row['email'],
            apiKey:      $row['api_key'],
            role:        $row['role'],
            pspProvider: $row['psp_provider'] ?? null,
            createdAt:   $row['created_at'],
        );
    }
}
