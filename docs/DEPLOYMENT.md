# Deployment

## Laravel Cloud (recommended)

Laravel Cloud deploys the app directly from your repository:

1. Push to a connected repo, create an app in your Cloud account
2. Set the environment variables from `CONFIGURATION.md` (app key, mail,
   database, Stripe keys)
3. Workers: add the queue worker; schedule via Cloud's scheduler support
4. Run `php artisan migrate --force` on first deploy, then
   `php artisan db:seed --class=PlanSeeder` once

## Generic VPS

1. **Web server** — point the docroot at `public/`, standard Laravel
   nginx config, TLS via certbot
2. **PHP** — 8.3+, extensions: pdo_mysql (or pdo_pgsql), bcmath, mbstring,
   xml, curl, intl; `opcache` enabled
3. **Deploy** — `composer install --no-dev --optimize-autoloader`,
   `npm ci && npm run build`, `php artisan migrate --force`,
   `php artisan config:cache && route:cache && view:cache`,
   `php artisan storage:link`
4. **Queue worker** — systemd/supervisor running
   `php artisan queue:work --sleep=1 --tries=3` (use Redis or the
   database driver)
5. **Scheduler** — one cron entry per minute:
   `* * * * * php /path/artisan schedule:run`
6. **Health** — `/up` (Laravel) is always-on; the administrator runs the
   richer checks from the System Health screen

## Backups

- Database: scheduled dumps (e.g. `mysqldump` or a package) stored off-box
- Storage: the `private` disk holds avatars and private files — back it up
  alongside the database; swap to S3 by setting the `private` filesystem
  disk in `config/filesystems.php` (uploads use the disk abstraction, no
  code changes)

## Post-deploy checklist

- [ ] `php artisan laraplate:status` green (no FAIL rows)
- [ ] Stripe webhook endpoint registered for production + secret set
- [ ] Queue worker + scheduler running
- [ ] Mail driver delivers (not `log`)
- [ ] `APP_DEBUG=false`, session driver not `database` at scale
- [ ] Smoke: register → verify → create organization → test checkout