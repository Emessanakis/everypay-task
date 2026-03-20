<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Lefteris\EverypayTask\Console\SendChargeReportCommand;
use Lefteris\EverypayTask\Infrastructure\DatabaseConnection;
use Lefteris\EverypayTask\Infrastructure\SymfonyMailerSender;
use Lefteris\EverypayTask\Repository\ChargeRepository;
use Lefteris\EverypayTask\Repository\MerchantRepository;
use Lefteris\EverypayTask\Service\ReportService;
use Symfony\Component\Console\Application;

Dotenv::createImmutable(__DIR__ . '/../')->load();

$db = new DatabaseConnection(
    host:     $_ENV['DB_HOST'],
    dbname:   $_ENV['DB_DATABASE'],
    username: $_ENV['DB_USERNAME'],
    password: $_ENV['DB_PASSWORD'],
);

$merchantRepository = new MerchantRepository($db);
$chargeRepository   = new ChargeRepository($db);

$emailSender = new SymfonyMailerSender(
    dsn:      $_ENV['MAIL_DSN'],
    from:     $_ENV['MAIL_FROM'],
    fromName: $_ENV['MAIL_FROM_NAME'],
);

$reportService = new ReportService($merchantRepository, $chargeRepository, $emailSender);

$app = new Application('EveryPay CLI', '1.0.0');
$app->addCommand(new SendChargeReportCommand($reportService));
$app->run();
