# Installation

## Requirements

- PHP **≥ 8.3** with `pdo_sqlite` (local) — `pdo_mysql` for MySQL deployments
- Composer 2.x
- Node.js ≥ 20 and npm
- SQLite (zero-config local default) or MySQL ≥ 8

## Fresh installation (< 30 minutes)

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database + catalog
php artisan migrate --force
php artisan laraplate:demo        # plans, role permissions, labeled demo data

# 4. Frontend
npm run build

# 5. Run
php artisan serve                 # http://localhost:8000
```

Open the app, register an account, and verify the installation:

```bash
php artisan laraplate:status
```

Checks environment, database, migrations, queue, mail, storage link, frontend
build and Stripe configuration. Any `FAIL` row blocks a healthy start.

### Queue worker (required for email)

Transactional mail is queued. In development:

```bash
php artisan queue:work
```

## Troubleshooting

| Symptom | Fix |
|---|---|
| Pages render unstyled | Run `npm run build` (or `npm run dev` for the dev server) — Tailwind compiles utilities from `resources/js` |
| Verification email never arrives | `MAIL_MAILER=log` writes to `storage/logs/laravel.log`; the queue worker must be running |
| 500 on fresh clone | Run the `laraplate:status` checks; the most common root cause is a missing migration or `APP_KEY` |
| Login throttled (429) | Auth routes allow 10 attempts/minute by default |
| spatie role errors | Role/permission bootstrap is self-healing, but `laraplate:demo` seeds the catalog explicitly |
| Windows: `storage:link` fails | Run the terminal as Administrator, or serve files from the local driver (dev default is the private disk via signed URLs) |

## Upgrading a buyer's project

This is a starter kit, not a package. Upgrades are manual merges; `CHANGELOG.md` documents breaking changes per release, and `docs/CUSTOMIZATION.md` explains how to keep custom modules mergeable.