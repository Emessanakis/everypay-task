# Project Structure

```
everypay-task/
│
├── bin/
│   └── console.php                     # CLI entry point – bootstraps Symfony Console
│
├── config/
│   └── services.php                    # Manual DI: wires all services and controllers
│
├── database/
│   ├── 01_schema.sql                   # DDL: merchants + charges tables
│   └── 02_seeds.sql                    # DML: 1 admin + 5 merchants + 30 days of charges
│
├── docker/
│   └── Dockerfile.app                  # PHP 8.2-cli image with PDO MySQL + Composer
│
├── docs/
│   ├── architecture.md                 # Layer overview, flows, design decisions
│   ├── project-structure.md            # This file
│   ├── api-requests.md                 # API reference with request examples
│   ├── tests-implementation.md         # Test strategy and how to run
│   └── console-email.md                # CLI report command + email subsystem
│
├── public/
│   └── index.php                       # HTTP entry point – loads env, routes requests
│
├── src/
│   ├── Console/
│   │   └── SendChargeReportCommand.php # `report:send` Symfony console command
│   │
│   ├── Controller/
│   │   ├── ChargeController.php        # POST /charge
│   │   ├── ChargesController.php       # GET /charges (all filter variants)
│   │   └── MerchantChargesController.php
│   │
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Charge.php
│   │   │   ├── Merchant.php
│   │   │   └── Money.php               # Value object: amount (cents) + currency
│   │   ├── Enum/
│   │   │   └── ChargeStatus.php        # succeeded | failed
│   │   └── Exception/
│   │       ├── DomainException.php
│   │       ├── NotFoundException.php
│   │       └── PaymentFailedException.php
│   │
│   ├── Infrastructure/
│   │   ├── ApiKeyAuthenticator.php     # Validates X-API-Key → Model\Merchant or null
│   │   ├── DatabaseConnection.php      # PDO factory (MySQL, utf8mb4, ERRMODE_EXCEPTION)
│   │   ├── EmailSenderInterface.php    # send(to, subject, body): void
│   │   ├── Request.php                 # HTTP request wrapper
│   │   ├── Response.php                # Fluent HTTP response builder
│   │   ├── SimpleEmailSender.php       # SMTP AUTH LOGIN via fsockopen (alternative)
│   │   └── SymfonyMailerSender.php     # Symfony Mailer via DSN (active implementation)
│   │
│   ├── Middleware/
│   │   └── AuthMiddleware.php          # X-API-Key guard; sets 'merchant' on Request
│   │
│   ├── Model/                          # Persistence read-models (hydrated from DB rows)
│   │   ├── Charge.php
│   │   └── Merchant.php
│   │
│   ├── Provider/
│   │   ├── PaymentProviderInterface.php  # charge(dto): string (transaction ID)
│   │   ├── FakeStripeProvider.php        # Requires card_number, cvv, expiry_month/year
│   │   ├── FakePaypalProvider.php        # Requires email, password
│   │   └── PaymentProviderFactory.php    # make(Merchant): PaymentProviderInterface
│   │
│   ├── Repository/
│   │   ├── ChargeRepositoryInterface.php
│   │   ├── ChargeRepository.php          # MySQL/PDO implementation
│   │   ├── MerchantRepositoryInterface.php
│   │   └── MerchantRepository.php        # MySQL/PDO implementation
│   │
│   ├── Router/
│   │   └── Router.php                    # FastRoute wrapper with middleware chaining
│   │
│   └── Service/
│       ├── ChargeService.php             # Orchestrates the full charge flow
│       ├── ReportService.php             # Builds and emails a charge summary report
│       ├── ChargeRequestDTO.php
│       └── ChargeResponseDTO.php
│
├── tests/
│   ├── Unit/
│   │   ├── ChargePaymentServiceTest.php  # ChargeService isolated with Mockery
│   │   ├── AuthMiddlewareTest.php        # AuthMiddleware: missing/invalid/valid API key
│   │   └── PaymentProviderFactoryTest.php# Factory dispatch + unknown provider error
│   └── Integration/
│       └── ChargeApiTest.php             # ChargeController wired with mocked ChargeService
│
├── .env                                  # Environment variables (DB, mail)
├── composer.json
├── docker-compose.yml                    # app + db (MySQL 8) + mailpit
└── phpunit.xml
```

---

## Database Schema

### `merchants`

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(36) PK | UUID v4 |
| name | VARCHAR(255) | |
| email | VARCHAR(255) | Used as report recipient |
| api_key | VARCHAR(64) UNIQUE | Value passed in `X-API-Key` header |
| role | ENUM('merchant','admin') | Default `merchant` |
| psp_provider | VARCHAR(50) | `fakeStripe` or `fakePaypal`; NULL for admin |
| created_at | TIMESTAMP | Auto-set on insert |

### `charges`

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(36) PK | UUID v4 |
| merchant_id | VARCHAR(36) FK | References `merchants.id` |
| amount | INT | Stored in cents |
| currency | VARCHAR(3) | Default `EUR` |
| status | VARCHAR(50) | `succeeded` / `failed` |
| transaction_id | VARCHAR(255) | Provider-issued ID; NULL on failure |
| created_at | TIMESTAMP | Auto-set on insert |

---

## Seeded Merchants

| Name | Role | Provider | API Key |
|------|------|----------|---------|
| Power User | admin | — | `ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d` |
| Acme Store | merchant | fakeStripe | `ak_live_acme_xK9mP2qL8nR4vT6wY1zA3bC5d` |
| Blue Ocean Shop | merchant | fakeStripe | `ak_live_blue_dF7hJ3kM9pS2uW5xB8eG1iN4q` |
| Green Market | merchant | fakeStripe | `ak_live_green_zQ6rT1vX4yA7cE0fH3jL9nP2s` |
| Nova Commerce | merchant | fakePaypal | `ak_live_nova_bD5gI2kN8pR4uW7xZ0aC3eH6j` |
| Pixel Goods | merchant | fakePaypal | `ak_live_pixel_mQ9sV3wY6zA1cF4hK7nP0rT2u` |
