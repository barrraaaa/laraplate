<?php

namespace App\Console\Commands;

use App\Modules\Core\FeatureManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\File;
use Throwable;

#[Signature('laraplate:status')]
#[Description('Validate the installation: environment, database, migrations, billing and build assets.')]
class LaraplateStatus extends Command
{
    /** @var array<int, array{0: string, 1: string, 2: string}> status, detail */
    private array $checks = [];

    private bool $failed = false;

    public function handle(): int
    {
        $this->info('Laraplate installation status'.PHP_EOL);

        $this->checkEnvironmentFile();
        $this->checkDatabase();
        $this->checkMigrations();
        $this->checkQueue();
        $this->checkMail();
        $this->checkStorageLink();
        $this->checkFrontendBuild();
        $this->checkBilling();

        $this->newLine();
        $this->table(['Check', 'Status', 'Detail'], $this->checks);

        if ($this->failed) {
            $this->newLine();
            $this->error('Some required checks failed. Run `php artisan laraplate:status` again after fixing them.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function checkEnvironmentFile(): void
    {
        $keySet = ! blank(config('app.key'));

        $this->record(
            'Environment file (.env)',
            $keySet ? 'ok' : 'fail',
            $keySet ? 'APP_KEY is set' : 'Missing APP_KEY — run `php artisan key:generate`',
        );
    }

    private function checkDatabase(): void
    {
        try {
            $connection = app(ConnectionResolverInterface::class)->connection();

            $connection->getPdo();
            $this->record(
                'Database connection', 'ok',
                config('database.default').' / '.$connection->getDatabaseName(),
            );
        } catch (Throwable $e) {
            $this->record('Database connection', 'fail', $e->getMessage());
        }
    }

    private function checkMigrations(): void
    {
        $pending = collect(app('migrator')->getMigrationFiles([database_path('migrations')]))
            ->keys()
            ->diff(app('migrator')->getRepository()->getRan())
            ->count();

        if ($pending === 0) {
            $this->record('Database migrations', 'ok', 'All migrations applied');
        } elseif ($pending <= 5) {
            $this->record('Database migrations', 'warn', "$pending pending — run `php artisan migrate`");
        } else {
            $this->record('Database migrations', 'fail', "$pending pending — run `php artisan migrate`");
        }
    }

    private function checkQueue(): void
    {
        $connection = config('queue.default');

        $this->record(
            'Queue worker', $connection === 'sync' ? 'warn' : 'ok',
            $connection === 'sync'
                ? 'queue.default=sync — run `php artisan queue:work` with a real driver in production'
                : "queue.default=$connection — run `php artisan queue:work`",
        );
    }

    private function checkMail(): void
    {
        $mailer = config('mail.default');

        if (app()->environment('production') && $mailer === 'log') {
            $this->record('Mail transport', 'fail', 'MAIL_MAILER=log is not usable in production');
        } elseif ($mailer === 'log') {
            $this->record('Mail transport', 'warn', 'MAIL_MAILER=log — emails are written to the log, not delivered');
        } else {
            $this->record('Mail transport', 'ok', "Using $mailer");
        }
    }

    private function checkStorageLink(): void
    {
        $linked = File::exists(public_path('storage'))
            || app()->environment('testing');

        $this->record(
            'Storage link', $linked ? 'ok' : 'warn',
            $linked ? 'public/storage is linked' : 'Missing — run `php artisan storage:link`',
        );
    }

    private function checkFrontendBuild(): void
    {
        $built = File::exists(public_path('build/manifest.json'));

        $this->record(
            'Frontend build', $built ? 'ok' : 'warn',
            $built
                ? 'public/build is present'
                : 'Missing — run `npm install && npm run build` (or `npm run dev` locally)',
        );
    }

    private function checkBilling(): void
    {
        if (! app(FeatureManager::class)->enabled('billing')) {
            $this->record('Billing (Stripe)', 'ok', 'Billing module disabled');

            return;
        }

        $missing = collect(['key', 'secret'])
            ->filter(fn (string $key): bool => blank(config('saas.billing.stripe.'.$key)));

        $this->record(
            'Billing (Stripe)', $missing->isEmpty() ? 'ok' : 'warn',
            $missing->isEmpty()
                ? 'Stripe keys present'
                : 'Missing: '.$missing->map(fn (string $key): string => 'STRIPE_'.strtoupper($key))->join(', ').' — set them before testing checkout',
        );
    }

    private function record(string $check, string $status, string $detail): void
    {
        if ($status === 'fail') {
            $this->failed = true;
        }

        $label = match ($status) {
            'ok' => '<fg=bright-green>OK</>',
            'warn' => '<fg=bright-yellow>WARN</>',
            'fail' => '<fg=bright-red>FAIL</>',
            default => $status,
        };

        $this->checks[] = ["<fg=bright-white>$check</>", $label, "<fg=gray>$detail</>"];
    }
}
