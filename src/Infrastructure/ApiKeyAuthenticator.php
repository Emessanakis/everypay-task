<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

use Lefteris\EverypayTask\Model\Merchant;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;

class ApiKeyAuthenticator
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
    ) {}

    public function authenticate(string $apiKey): ?Merchant
    {
        if (trim($apiKey) === '') {
            return null;
        }

        return $this->merchantRepository->findByApiKey($apiKey);
    }
}
