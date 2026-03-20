<?php

declare(strict_types=1);

use Lefteris\EverypayTask\Controller\ChargeController;
use Lefteris\EverypayTask\Controller\ChargesController;
use Lefteris\EverypayTask\Infrastructure\ApiKeyAuthenticator;
use Lefteris\EverypayTask\Infrastructure\DatabaseConnection;
use Lefteris\EverypayTask\Middleware\AuthMiddleware;
use Lefteris\EverypayTask\Provider\PaymentProviderFactory;
use Lefteris\EverypayTask\Repository\ChargeRepository;
use Lefteris\EverypayTask\Repository\MerchantRepository;
use Lefteris\EverypayTask\Service\ChargeService;

$db = new DatabaseConnection(
    host:     $_ENV['DB_HOST'],
    dbname:   $_ENV['DB_DATABASE'],
    username: $_ENV['DB_USERNAME'],
    password: $_ENV['DB_PASSWORD'],
);

$merchantRepository = new MerchantRepository($db);
$chargeRepository   = new ChargeRepository($db);
$providerFactory    = new PaymentProviderFactory();

$chargeService = new ChargeService(
    $merchantRepository,
    $chargeRepository,
    $providerFactory,
);

$authenticator  = new ApiKeyAuthenticator($merchantRepository);
$authMiddleware = new AuthMiddleware($authenticator);

return [
    'chargeController'          => new ChargeController($chargeService),
    'chargesController' => new ChargesController($merchantRepository, $chargeRepository),
    'authMiddleware'            => $authMiddleware,
];
