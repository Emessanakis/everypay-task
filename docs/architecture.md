# Architecture

## Layer Map

```
┌──────────────────────────────────────────────────────────┐
│  Presentation   (src/Controller, src/Console,            │
│                  src/Middleware, src/Router)              │
├──────────────────────────────────────────────────────────┤
│  Application    (src/Service)                            │
│  – Use-case orchestration, DTOs                          │
├──────────────────────────────────────────────────────────┤
│  Infrastructure (src/Infrastructure, src/Provider,       │
│                  src/Repository, src/Model)              │
│  – DB, email, HTTP wrappers, payment providers           │
├──────────────────────────────────────────────────────────┤
│  Domain         (src/Domain)                             │
│  – Entities, value objects, enums, exceptions            │
│    (no external dependencies)                            │
└──────────────────────────────────────────────────────────┘
```

Dependency direction: Presentation → Application → Infrastructure → Domain.

---

## Request Flow: POST /charge

```
HTTP POST /charge
    │
    ▼
public/index.php
    │  loads .env, builds DI container (config/services.php)
    ▼
Router::dispatch(Request, Response)
    │
    ▼
AuthMiddleware
    │  reads X-API-Key header → MerchantRepository → DB
    │  sets request attribute 'merchant'
    ▼
ChargeController::handle(Request, Response)
    │  validates role (rejects admin)
    │  parses + validates JSON body
    │  builds ChargeRequestDTO
    ▼
ChargeService::charge(ChargeRequestDTO)
    │  MerchantRepository::findByApiKey()
    │  PaymentProviderFactory::make(merchant)
    │  FakeStripe/PaypalProvider::charge(dto)  ──► PaymentFailedException (~10%)
    │  ChargeRepository::save(Charge)
    │  returns ChargeResponseDTO
    ▼
Response 201 / 402 / 404 / 500
```

## Request Flow: report:send (CLI)

```
docker exec everypay-app php bin/console.php report:send --merchant-id=<uuid> [--from=Y-m-d] [--to=Y-m-d]
    │
    ▼
SendChargeReportCommand::execute()
    │  validates options, parses dates (default: last 7 days → today)
    ▼
ReportService::sendReport(merchantId, from, to)
    │  MerchantRepository::findById()
    │  ChargeRepository::findByMerchantAndDateRange()
    │  builds plain-text report
    ▼
SymfonyMailerSender::send(to, subject, body)
    │  Symfony Mailer via MAIL_DSN → Mailpit (local) or any SMTP
    ▼
Email delivered to merchant
```

---

## Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| Layered architecture | Appropriate complexity for the project scope |
| Manual DI container | No framework dependency; explicit wiring is readable |
| Repository pattern | Decouples business logic from MySQL; enables mocking in tests |
| Fake PSPs with ~10% decline rate | Simulates realistic payment flows without real API calls |
| Amounts stored as integers (cents) | Avoids floating-point rounding errors |
| Roles: `merchant` vs `admin` | Merchants charge, admins query |
| API key authentication | Stateless; fits payment API conventions |
| Symfony Mailer + Mailpit | Battle-tested email library; Mailpit catches mail locally without delivery |

---

## Extensibility Points

- **New payment provider** — implement `PaymentProviderInterface`, register in `PaymentProviderFactory`
- **New persistence backend** — implement `ChargeRepositoryInterface` / `MerchantRepositoryInterface`
- **New email transport** — implement `EmailSenderInterface`, swap in `bin/console.php`
- **Stronger auth** — replace `ApiKeyAuthenticator` with JWT/OAuth without touching controllers
