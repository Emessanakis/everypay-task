<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Repository;

use Lefteris\EverypayTask\Model\Merchant;

interface MerchantRepositoryInterface
{
    public function findByApiKey(string $apiKey): ?Merchant;

    public function findById(string $id): ?Merchant;
}
