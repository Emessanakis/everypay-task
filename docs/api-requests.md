# API Requests

Base URL: `http://localhost:8000`

All requests require the `X-API-Key` header.
Amounts are **integers in cents** (e.g. `1400` = €14.00).

---

## Available API Keys

| Merchant        | Role     | Provider    | API Key                                      |
|-----------------|----------|-------------|----------------------------------------------|
| Power User      | admin    | —           | `ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d`  |
| Acme Store      | merchant | fakeStripe  | `ak_live_acme_xK9mP2qL8nR4vT6wY1zA3bC5d`    |
| Blue Ocean Shop | merchant | fakeStripe  | `ak_live_blue_dF7hJ3kM9pS2uW5xB8eG1iN4q`    |
| Green Market    | merchant | fakeStripe  | `ak_live_green_zQ6rT1vX4yA7cE0fH3jL9nP2s`   |
| Nova Commerce   | merchant | fakePaypal  | `ak_live_nova_bD5gI2kN8pR4uW7xZ0aC3eH6j`    |
| Pixel Goods     | merchant | fakePaypal  | `ak_live_pixel_mQ9sV3wY6zA1cF4hK7nP0rT2u`   |

---

## POST /charge

Create a new charge. Use a **merchant** API key (admin keys will be rejected).

### fakeStripe merchant

```
POST http://localhost:8000/charge
X-API-Key: ak_live_acme_xK9mP2qL8nR4vT6wY1zA3bC5d
Content-Type: application/json
```

```json
{
    "amount": 1400,
    "currency": "EUR",
    "card_number": "4111111111111111",
    "cvv": "123",
    "expiry_month": 12,
    "expiry_year": 2027
}
```

### fakePaypal merchant

```
POST http://localhost:8000/charge
X-API-Key: ak_live_nova_bD5gI2kN8pR4uW7xZ0aC3eH6j
Content-Type: application/json
```

```json
{
    "amount": 4000,
    "currency": "EUR",
    "email": "test@example.com",
    "password": "secret123"
}
```

---

## GET /charges

Get all charges. Requires an **admin** API key.

```
GET http://localhost:8000/charges
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```

---

## GET /charges/{date}

Get all charges on a specific date (`YYYY-MM-DD`).

```
GET http://localhost:8000/charges/2026-03-18
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```

---

## GET /charges/{from},{to}

Get all charges within a date range.

```
GET http://localhost:8000/charges/2026-03-01,2026-03-18
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```

---

## GET /charges/{merchantId}

Get all charges for a specific merchant. Replace `{merchantId}` with the merchant's UUID (visible in the response of any charges query).

```
GET http://localhost:8000/charges/{merchantId}
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```

---

## GET /charges/{merchantId}/{date}

Get charges for a specific merchant on a specific date.

```
GET http://localhost:8000/charges/{merchantId}/2026-03-18
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```

---

## GET /charges/{merchantId}/{from},{to}

Get charges for a specific merchant within a date range.

```
GET http://localhost:8000/charges/{merchantId}/2026-03-01,2026-03-18
X-API-Key: ak_admin_pwrUsr_X7kQ2mN9pL4rT1vZ6wA3bC8d
```
