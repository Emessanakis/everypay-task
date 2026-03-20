# Tests

PHPUnit 10 + Mockery 1.6. Two suites — no database or network required for either.

| Suite | Location | What it tests |
|-------|----------|---------------|
| Unit | `tests/Unit/` | `ChargeService`, `AuthMiddleware`, `PaymentProviderFactory` isolated with all collaborators mocked |
| Integration | `tests/Integration/` | `ChargeController` wired with a mocked `ChargeService` |

---

## Running

```bash
# All suites
docker exec everypay-app ./vendor/bin/phpunit

# One suite
docker exec everypay-app ./vendor/bin/phpunit --testsuite Unit
docker exec everypay-app ./vendor/bin/phpunit --testsuite Integration

```

---

## Unit: `ChargePaymentServiceTest`

Tests `ChargeService` in isolation. All dependencies are Mockery mocks.

| Test | Scenario |
|------|----------|
| `testChargeThrowsNotFoundExceptionWhenMerchantDoesNotExist` | `findByApiKey()` returns null → `NotFoundException` thrown, `save()` never called |
| `testChargeSucceedsAndPersistsCharge` | Provider returns transaction ID → `save()` called once with `status=succeeded`, DTO returned |
| `testChargeStoresFailedChargeAndRethrowsPaymentFailedException` | Provider throws → `save()` called with `status=failed, transactionId=null`, exception re-thrown |
| `testChargeReturnsDtoWithCorrectAmountAndCurrency` | Amount and currency pass through without mutation |

---

## Unit: `AuthMiddlewareTest`

Tests `AuthMiddleware` in isolation. `ApiKeyAuthenticator` is mocked; uses `SpyAuthResponse` to suppress real HTTP output.

| Test | Scenario |
|------|----------|
| `testReturns401WhenApiKeyHeaderIsMissing` | No `X-API-Key` header → 401, `$next` never called |
| `testReturns401WhenApiKeyIsInvalid` | Authenticator returns null → 401, `$next` never called |
| `testSetsMerchantAttributeAndCallsNextOnValidKey` | Authenticator returns merchant → `setAttribute('merchant', …)` called, `$next` invoked |

---

## Unit: `PaymentProviderFactoryTest`

Tests `PaymentProviderFactory::make()` directly — no mocks needed.

| Test | Scenario |
|------|----------|
| `testMakeReturnsFakeStripeProvider` | `pspProvider = fakeStripe` → returns `FakeStripeProvider` instance |
| `testMakeReturnsFakePaypalProvider` | `pspProvider = fakePaypal` → returns `FakePaypalProvider` instance |
| `testMakeThrowsForUnknownProvider` | `pspProvider = unknown` → throws `InvalidArgumentException` |

---

## Integration: `ChargeApiTest`

Tests `ChargeController` + `Request` + `Response`. Uses `SpyResponse` to capture HTTP status/body without emitting real output.

| Test | Expected status |
|------|----------------|
| Admin caller | 403 (admin accounts cannot process payments) |
| Invalid JSON body | 400 |
| Missing `amount` | 422 |
| `amount` is a string, not integer | 422 |
| Successful charge | 201 with full DTO fields |
| `ChargeService` throws `NotFoundException` | 404 |
| `ChargeService` throws `PaymentFailedException` | 402 |
| `ChargeService` throws unexpected exception | 500 (internal message hidden) |

---

## Not Yet Covered

| Area | Type |
|------|------|
| `ChargesController` (GET /charges*) | Integration |
| `ReportService` | Unit |
| `FakeStripeProvider` / `FakePaypalProvider` | Unit |
| `ChargeRepository` / `MerchantRepository` | Integration (needs test DB) |
| `SendChargeReportCommand` | Unit |

---

## Mockery Notes

Always call `Mockery::close()` in `tearDown()` — it verifies `once()` / `times()` expectations.

```php
// Expect a specific argument and return a value
$this->merchantRepository
    ->shouldReceive('findByApiKey')
    ->with('test-api-key')
    ->andReturn($merchant);

// Expect exactly one call matching a predicate
$this->chargeRepository
    ->shouldReceive('save')
    ->once()
    ->with(Mockery::on(static fn(Charge $c) => $c->status === 'succeeded'));

// Expect a call that throws
$this->provider
    ->shouldReceive('charge')
    ->andThrow(new PaymentFailedException('card declined'));
```
