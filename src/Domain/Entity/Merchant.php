<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Domain\Entity;

final class Merchant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $apiKey,
        public readonly string $pspProvider,
        public readonly bool   $admin = false,
    ) {}

    public function isAdmin(): bool
    {
        return $this->admin;
    }
}
