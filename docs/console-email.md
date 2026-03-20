# Console Command & Email

## Command: `report:send`

Collects charges for a merchant over a date range and emails a summary report.

```bash
docker exec everypay-app php bin/console.php report:send --merchant-id=<uuid> [--from=Y-m-d] [--to=Y-m-d]
```

| Option | Required | Default | Description |
|--------|----------|---------|-------------|
| `--merchant-id` | yes | — | UUID of the target merchant |
| `--from` | no | 7 days ago | Report start date (`Y-m-d`) |
| `--to` | no | today | Report end date (`Y-m-d`) |

Both dates are inclusive (`00:00:00` through `23:59:59`).

### Examples

```bash
# Last 7 days (defaults)
docker exec everypay-app php bin/console.php report:send --merchant-id=<uuid>

# Explicit range
docker exec everypay-app php bin/console.php report:send --merchant-id=<uuid> --from=2026-03-01 --to=2026-03-31

```

---

## Report Email

**Subject:** `Charge Report for {Merchant Name} ({from} – {to})`

**Body format:**

```
Charge Report — Acme Store
============================================================
Period : 2026-03-01 to 2026-03-31
Charges: 30 total (succeeded: 26, failed: 4)
Revenue: 263700 cents (2637.00 EUR)

Details:
------------------------------------------------------------
[2026-03-31 09:30:00] id=... | 16500 EUR | status=succeeded | txn=txn_acme_030
[2026-03-30 12:00:00] id=... | 9400 EUR  | status=succeeded | txn=txn_acme_029
...
```

If no charges exist in the period, the details section shows:
```
No charges found for this period.
```

---

## Email Sender

Reports are sent via **Symfony Mailer** (`SymfonyMailerSender`), configured by a DSN in `.env`:

```ini
MAIL_DSN=smtp://mailpit:1025
MAIL_FROM=test.task.email@example.com
MAIL_FROM_NAME="EveryPay Task"
```

In the Docker setup, **Mailpit** acts as a local SMTP catcher — all emails are captured and viewable at `http://localhost:8025`. No mail is delivered to real addresses.

To use a real SMTP provider, update `MAIL_DSN` to the appropriate Symfony Mailer DSN (e.g. `smtp://user:pass@smtp.example.com:587`).

---

## Extending

To swap the email transport, implement `EmailSenderInterface` and update the instantiation in `bin/console.php`. No changes to `ReportService` or the command are required.
