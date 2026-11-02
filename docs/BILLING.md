# Billing setup (Stripe)

The boilerplate ships with a **provider-adapter boundary**:
`App\Modules\Billing\Providers\BillingGateway` (interface) +
`StripeGateway` (Cashier implementation). Domain code never talks to
Stripe directly; a second provider is a new implementation.

The **organization** is the billable owner (the personal organization in
single-user mode).

## 1. Stripe account + keys

1. Create a Stripe account (test mode for development)
2. Set `.env`:
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...   # after step 3
   ```
3. `laraplate:status` should now report `OK` for Billing

## 2. Prices

Create monthly + yearly prices per paid plan (the sample catalog uses
`Pro` and `Business`), then set:

```
STRIPE_PRICE_PRO_MONTHLY=price_...
STRIPE_PRICE_PRO_YEARLY=price_...
STRIPE_PRICE_BUSINESS_MONTHLY=price_...
STRIPE_PRICE_BUSINESS_YEARLY=price_...
```

Re-seed plans afterwards: `php artisan db:seed --class=PlanSeeder`

## 3. Webhooks

Webhook endpoint: **`POST /stripe/webhook`** (name `cashier.webhook`,
CSRF-exempt, signature-verified by Cashier). Configure these events:

- `customer.subscription.created` / `updated` / `deleted`
- `invoice.payment_succeeded` / `invoice.payment_failed` / `invoice.payment_action_required`
- `customer.updated` / `customer.deleted`
- `payment_method.automatically_updated`

Local testing with real events:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## How processing stays safe

- **Idempotency** — every payment event lands in `transactions` under a
  unique `stripe_event_id`; replays are no-ops. Invoices are mirror-
  upserted the same way.
- **Entitlement resolution** — the subscription's price maps back to a
  `Plan`; canceled subscriptions keep entitlements through the grace
  period, then fall back to the free plan.
- **Users are notified** — `PaymentSucceeded` / `PaymentFailed` ship as
  queued mail **and** in-app notifications.

## Checkout & portal

- Checkout sessions: `POST /billing/checkout` (plan + interval), guarded
  by the `manageBilling` policy (owner/admin)
- Cancel / resume: `POST /billing/cancel`, `/billing/resume`
- Stripe customer portal: `GET /billing/portal`

## Going live

1. Enable production Stripe keys and update prices to live ids
2. Point the webhook endpoint at your production URL
3. Keep `cache`/`queue` on real drivers and verify the scheduler runs
4. Test a full checkout with a Stripe test card, then one real low-value
   charge that you refund immediately

## Notes

- Without Stripe keys the app runs fully on the free plan and the billing
  page shows a configuration banner — a safe demo state
- Trials: set `trial_days`/`trial_ends_at` through Cashier's builder or
  subscribe; local behavior mirrors Stripe's via webhooks