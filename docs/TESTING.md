# Testing

## Running the suites

```bash
php artisan test                       # PHPUnit (feature + unit)
vendor/bin/pint --test                 # code style
vendor/bin/phpstan analyse             # static analysis, level 5
composer check                         # all three in one go
npm run lint                           # ESLint + Prettier for resources/js
```

CI (`.github/workflows/tests.yml`) runs the PHP checks across **PHP 8.3
and 8.4**, on SQLite and MySQL.

## Structure

- `tests/Feature/**` — HTTP-level flows (auth, tenancy, billing,
  entitlements, admin, projects, notifications)
- `tests/Unit/**` — pure units (FeatureManager, etc.)
- Models factories live next to their modules:
  `database/factories/{Billing,Entitlements,Audit,Projects}…`

## Conventions worth knowing

- Every tenancy test pins the active organization with
  `withSession(['current_organization_id' => …])` — the middleware
  otherwise falls back to the user's personal org
- spatie's permission cache is reset in `Tests\TestCase::setUp`; when a
  test flips a user's permissions-team mid-test, re-hydrate the model
  with `$user->fresh()`
- Sanctum is **stateful in tests**: to exercise bearer tokens, flush the
  session + web guard first (`flushSession()` + `Auth::guard('web')->forgetUser()`)
- Plans in tests need price ids when the flow resolves them — helpers in
  the billing tests demonstrate (`givePriceIds()`)

## Stripe webhook tests

`WebhookIdempotencyTest` posts minimal Stripe event payloads at
`/stripe/webhook` (signature verification is skipped when
`STRIPE_WEBHOOK_SECRET` is unset — the documented local dev behavior) and
asserts replay-safety: the same event twice still yields one transaction
and one invoice.

## Coverage targets

≥ 80% on core domain services; every PRD acceptance criterion (§13) maps
to at least one test — cross-tenant tampering, last-owner protection,
webhook idempotency, quota enforcement and role hierarchy included.