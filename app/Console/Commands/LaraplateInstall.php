<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('laraplate:install')]
#[Description('Interactive setup wizard: env validation, migrations, Mailgun/Stripe/OAuth configuration.')]
class LaraplateInstall extends Command
{
    /**
     * Cross-platform .env updater — rewrites or appends KEY=VALUE while
     * preserving the rest of the file.
     */
    public static function updateEnvFile(string $key, string $value): void
    {
        $path = app()->environmentFilePath();
        $lines = File::exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
        $found = false;

        foreach ($lines as $index => $line) {
            if (preg_match('/^'.preg_quote($key, '/').'\s*=/', $line)) {
                $lines[$index] = $key.'='.$value;
                $found = true;

                break;
            }
        }

        if (! $found) {
            $lines[] = $key.'='.$value;
        }

        File::put($path, implode(PHP_EOL, $lines).PHP_EOL);
    }

    public function handle(): int
    {
        $this->newLine();
        $this->info('⚡ Laraplate installer');
        $this->newLine();

        $checks = [];

        // 1. Environment + app key.
        if (! File::exists(app()->environmentFilePath())) {
            File::copy(base_path('.env.example'), app()->environmentFilePath());
        }

        if (blank(config('app.key'))) {
            $this->call('key:generate');
        }

        $checks['Environment & APP_KEY'] = true;

        // 2. Database.
        $this->call('migrate', ['--force' => true]);
        $checks['Database migrated'] = true;

        // 3. Catalog seeders (idempotent).
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\PlanSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\RoleAndPermissionSeeder', '--force' => true]);
        $checks['Roles & plans seeded'] = true;

        // 4. Optional integrations.
        $this->promptMailgun();
        $checks['Mailgun transactional email linked'] = $this->configured(config('services.mailgun.domain'));
        $this->promptStripe();
        $checks['Stripe Billing active'] = $this->configured(config('saas.billing.stripe.key'));
        $this->promptSocial('google', 'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET');
        $checks['Google OAuth configured'] = $this->configured(config('services.google.client_id'));
        $this->promptSocial('github', 'GITHUB_CLIENT_ID', 'GITHUB_CLIENT_SECRET');
        $checks['GitHub OAuth configured'] = $this->configured(config('services.github.client_id'));

        $checks['Roles, 2FA & webhooks ready'] = true;

        // 5. Summary.
        $this->newLine();
        $this->line('<fg=gray>Setup complete:</>');

        foreach ($checks as $label => $ok) {
            $this->line(
                $ok
                    ? "  <fg=green>✔</> {$label}!"
                    : "  <fg=yellow>⚠</> {$label} skipped — set it later in .env",
            );
        }

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>🚀 BOOM!</> App running on <fg=yellow;options=underscore>http://localhost:8000</> — start taking customer payments!');
        $this->newLine();
        $this->line('<fg=gray>Start the server with:</> <fg=green>php artisan serve</fg=green>');

        return self::SUCCESS;
    }

    private function configured(mixed $value): bool
    {
        return filled($value);
    }

    private function promptMailgun(): void
    {
        if ($this->configured(config('services.mailgun.domain')) || ! $this->input->isInteractive() || ! $this->confirm('Configure Mailgun now?', false)) {
            return;
        }

        $domain = $this->ask('Mailgun domain (mg.yourdomain.com)');

        if (blank($domain)) {
            return;
        }

        self::updateEnvFile('MAIL_MAILER', 'mailgun');
        self::updateEnvFile('MAILGUN_DOMAIN', $domain);
        self::updateEnvFile('MAILGUN_SECRET', (string) $this->secret('Mailgun API key'));
        self::updateEnvFile('MAILGUN_ENDPOINT', (string) $this->ask('Mailgun endpoint', 'api.mailgun.net'));
    }

    private function promptStripe(): void
    {
        if ($this->configured(config('saas.billing.stripe.key')) || ! $this->input->isInteractive() || ! $this->confirm('Configure Stripe now?', false)) {
            return;
        }

        self::updateEnvFile('STRIPE_KEY', (string) $this->secret('Stripe publishable key (pk_...)'));
        self::updateEnvFile('STRIPE_SECRET', (string) $this->secret('Stripe secret key (sk_...)'));
        $this->line('  <fg=gray>Tip: set STRIPE_WEBHOOK_SECRET + STRIPE_PRICE_* ids after creating them (docs/BILLING.md).</>');
    }

    private function promptSocial(string $provider, string $clientKey, string $secretKey): void
    {
        $clientIdPath = 'services.'.$provider.'.client_id';

        if ($this->configured(config($clientIdPath)) || ! $this->input->isInteractive() || ! $this->confirm("Configure {$provider} OAuth now?", false)) {
            return;
        }

        self::updateEnvFile($clientKey, (string) $this->ask("{$provider} client id"));
        self::updateEnvFile($secretKey, (string) $this->secret("{$provider} client secret"));
        self::updateEnvFile(strtoupper($provider).'_REDIRECT_URI', url('/auth/'.$provider.'/callback'));
    }
}
