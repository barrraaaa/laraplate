# Laraplate

**Production-oriented Laravel SaaS boilerplate** — authentication, tenancy, billing, entitlements and an admin panel, ready for single-user and team-based subscription products.

Built on Laravel 13 · Inertia + React 19 + TypeScript · Tailwind CSS 4 + shadcn/ui · Stripe (Cashier).

## Feature modules

| Area | Highlights |
|---|---|
| Identity | Email/password auth, verification, password reset, session basics, account deletion, queued mail |
| Tenancy | `SAAS_MODE=single\|team\|hybrid`, organizations, invitations (hashed tokens, expiry), context switching, ownership transfer with last-owner protection |
| Authorization | Baseline roles (owner/admin/member/viewer) + granular, organization-scoped permissions (spatie) with a self-healing catalog |
| Billing | Stripe via a provider-adapter boundary: plans, checkout, webhooks (idempotent), cancel/resume/portal, invoice + transaction mirrors |
| Entitlements | Feature flags, quotas, usage recording, limit enforcement — one API (`can` / `quota` / `usage` / `record` / `overLimit`) |
| Admin panel | Platform dashboard (MRR, signups chart), users, organizations, subscriptions, transactions, invoices, audit stream, system health |
| Operations | Audit log (tenant + actor aware), in-app notifications, Sanctum API tokens with abilities, rate limiting, spatie health checks |
| Example module | A complete **Projects** slice — the documented copy-me pattern (tenancy, policy, quota enforcement, UI) |

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan laraplate:demo            # plans, roles + labeled demo data
npm install && npm run build

php artisan serve                     # http://localhost:8000
```

Demo login (after `laraplate:demo`): `demo@laraplate.test` / `password` (platform admin).

Verify the install: `php artisan laraplate:status`. Run a worker for queued mail: `php artisan queue:work`.

## Documentation

- [Installation](docs/INSTALLATION.md) — requirements, fresh-clone steps, troubleshooting
- [Configuration](docs/CONFIGURATION.md) — `config/saas.php`, env vars, roles & permissions
- [Billing setup](docs/BILLING.md) — Stripe keys, prices, webhooks, going live
- [Tenancy](docs/TENANCY.md) — modes, isolation model, invitations
- [Customization](docs/CUSTOMIZATION.md) — frontend architecture, adding a module, branding
- [Testing](docs/TESTING.md) — running suites, quality gates, webhook fixtures
- [Deployment](docs/DEPLOYMENT.md) — Laravel Cloud & VPS, workers, scheduler, backups
- [Roadmap](docs/ROADMAP.md)

## Quality

`composer check` runs Pint + PHPStan (level 5) + the PHPUnit suite (>90 tests). CI matrix: PHP 8.3/8.4, SQLite + MySQL.

## License

Commercial. See [LICENSE.md](LICENSE.md) for tiers and terms.