<?php

declare(strict_types=1);

use Lefteris\EverypayTask\Controller\ChargeController;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Router\Router;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = require __DIR__ . '/../config/services.php';

$request  = new Request();
$response = new Response();

$datePattern  = '\d{4}-\d{2}-\d{2}';
$rangePattern = '\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2}';

$router = new Router();
$router->addMiddleware($container['authMiddleware']);
$router->addRoute('POST', '/charge', [$container['chargeController'], 'handle']);
$router->addRoute('GET', '/charges', [$container['chargesController'], 'handle']);
$router->addRoute('GET', "/charges/{date:{$datePattern}}", [$container['chargesController'], 'handle']);
$router->addRoute('GET', "/charges/{range:{$rangePattern}}", [$container['chargesController'], 'handle']);
$router->addRoute('GET', '/charges/{merchantId}', [$container['chargesController'], 'handle']);
$router->addRoute('GET', "/charges/{merchantId}/{date:{$datePattern}}", [$container['chargesController'], 'handle']);
$router->addRoute('GET', "/charges/{merchantId}/{range:{$rangePattern}}", [$container['chargesController'], 'handle']);

$router->dispatch($request, $response);
