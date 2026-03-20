# EveryPay Task

PHP 8.2 payment-processing service built without a full framework. Merchants can charge payments through fake PSP implementations (fakeStripe, fakePaypal). Includes an HTTP API, API key authentication, charge persistence, and a CLI command that emails charge reports.

---

## Requirements

- Docker + Docker Compose

---

## Running the Application

**First time (builds images and starts everything):**

```bash
docker-compose up --build
```

**Subsequently:**

```bash
docker-compose up
```

Composer dependencies are installed automatically on container start.

This starts three containers:
- **app** — PHP 8.2 server at `http://localhost:8000`
- **db** — MySQL 8 (schema + seeds auto-applied on first run)
- **mailpit** — local mail catcher at `http://localhost:8025`

---

## API

All requests require an `X-API-Key` header. See [docs/api-requests.md](docs/api-requests.md) for the full reference.

### POST /charge

Create a charge. Use a **merchant** key.

**fakeStripe merchant:**
```
POST http://localhost:8000/charge
X-API-Key: ak_live_acme_xK9mP2qL8nR4vT6wY1zA3bC5d
Content-Type: application/json

{
    "amount": 1400,
    "currency": "EUR",
    "card_number": "4111111111111111",
    "cvv": "123",
    "expiry_month": 12,
    "expiry_year": 2027
}
```

**fakePaypal merchant:**
```
POST http://localhost:8000/charge
X-API-Key: ak_live_nova_bD5gI2kN8pR4uW7xZ0aC3eH6j
Content-Type: application/json

{
    "amount": 4000,
    "currency": "EUR",
    "email": "test@example.com",
    "password": "secret123"
}
```

### GET /charges

Query charges. Requires the **admin** key (`ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d`).

```
GET http://localhost:8000/charges
GET http://localhost:8000/charges/2026-03-18
GET http://localhost:8000/charges/2026-03-01,2026-03-18
GET http://localhost:8000/charges/{merchantId}
GET http://localhost:8000/charges/{merchantId}/2026-03-18
GET http://localhost:8000/charges/{merchantId}/2026-03-01,2026-03-18
```

---

## Charge Report (CLI)

Sends a charge summary email to the merchant. Captured locally by Mailpit at `http://localhost:8025`.

```bash
docker exec everypay-app php bin/console.php report:send \
    --merchant-id=<uuid> \
    --from=2026-03-01 \
    --to=2026-03-31
```

`--from` and `--to` default to the last 7 days. See [docs/console-email.md](docs/console-email.md) for more.

To get a merchant UUID, call `GET /charges` with the admin key and copy an `merchant_id` from the response.

---

## Tests

```bash
docker exec everypay-app ./vendor/bin/phpunit
```

See [docs/tests-implementation.md](docs/tests-implementation.md) for details.

---

## Docs

| File | Content |
|------|---------|
| [docs/architecture.md](docs/architecture.md) | Layer map, request flows, design decisions |
| [docs/project-structure.md](docs/project-structure.md) | Directory layout, DB schema, seeded API keys |
| [docs/api-requests.md](docs/api-requests.md) | Full API reference with example requests |
| [docs/console-email.md](docs/console-email.md) | CLI command reference, email format |
| [docs/tests-implementation.md](docs/tests-implementation.md) | Test strategy and coverage |

---

## Assumptions & Trade-offs

- **No framework** — routing (FastRoute), console (Symfony Console), and email (Symfony Mailer) are pulled in as focused libraries. Everything else is hand-rolled.
- **MySQL** — chosen for familiarity; the repository interfaces make it straightforward to swap out.
- **Roles via DB enum** — kept simple (`merchant` / `admin`) rather than a separate permissions system.
- **~10% simulated decline rate** — both fake PSPs randomly decline to make the failure path testable end-to-end.
- **Amounts in cents** — avoids floating-point issues; all monetary values are integers.
- **Mailpit for local email** — catches all outgoing mail without delivering to real addresses. Update `MAIL_DSN` in `.env` for a real SMTP provider.
- **Single git commit** — the project was developed as a self-contained assignment. Version control was used, but incremental commits were not made during development since the iterative history added no value in this context.
