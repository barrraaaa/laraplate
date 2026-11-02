# Configuration

All product behavior switches live in **`config/saas.php`** and `.env`.
Buyers should not edit core code for these knobs.

## Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | Laraplate | Product name shown in the UI + emails |
| `SAAS_MODE` | `single` | `single` \| `team` \| `hybrid` — see [Tenancy](TENANCY.md) |
| `SAAS_BILLING_PROVIDER` | `stripe` | Billing gateway implementation |
| `STRIPE_KEY` / `STRIPE_SECRET` | — | Stripe API keys (checkout + customer sync) |
| `STRIPE_WEBHOOK_SECRET` | — | Webhook signing secret (see [Billing](BILLING.md)) |
| `STRIPE_PRICE_PRO_MONTHLY` etc. | — | Monthly/yearly price ids per plan (config catalog) |
| `SAAS_INVITATION_EXPIRY_DAYS` | `3` | Invitation link lifetime |
| `SAAS_USAGE_PERIOD` | `monthly` | Usage metering window |
| `HEALTH_NOTIFICATIONS_ENABLED` | `true` | spatie health check notifications |

## `config/saas.php`

- **`mode`** — tenancy mode gate
- **`features`** — module switches. Referencing a disabled feature throws
  `FeatureDisabledException` (fail clearly; never a silent no-op):
  `billing`, `usage_limits`, `api_tokens`, `webhooks`, `audit_logs`, `organizations`
- **`plans`** — the plan catalog (`free_slug` + sample Starter/Pro/Business
  entries with `features` flags and `quotas`, e.g. `projects.max`). Seeded
  idempotently via `PlanSeeder` (`php artisan db:seed --class=PlanSeeder`)
- **`roles`** — granular permission catalog per spatie role
  (`organization-owner`, `-admin`, `-member`, `-viewer`). Membership roles
  (the curated source of truth) mirror into these automatically
- **`usage`** — metering period
- **`api_tokens.abilities`** — the ability set offered by the token UI
- **`invitations.expiry_days`** — invite link TTL
- **`billing`** — provider + Stripe credentials (config-cached safe)

## Adding a plan

1. Append an entry to `plans.catalog` in `config/saas.php`
2. Re-run `php artisan db:seed --class=PlanSeeder`
3. (Paid plans) create the corresponding prices in Stripe and set the
   `STRIPE_PRICE_*` env vars

Plans with no price ids are free and skip checkout entirely.

## Roles & permissions

Baseline membership roles: `owner` > `admin` > `member` > `viewer`.

- The **membership row** is the source of truth (`memberships.role`).
- Every write mirrors into spatie (organization-scoped via teams); reads
  use `$user->can('ability')` or policy helpers like
  `$user->isOrganizationAdmin($organization)`.
- To add an ability: register it in `saas.roles` for the roles that grant
  it, then check `can('your.ability')` in controllers.

## Health checks

Registered in `AppServiceProvider` (environment, debug mode, database,
cache, queue, disk). Results are stored by the configured result store and
rendered on the admin System Health screen.