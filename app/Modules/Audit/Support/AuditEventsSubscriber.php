<?php

namespace App\Modules\Audit\Support;

use App\Models\User;
use App\Modules\Audit\Services\Auditing;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Wires security-relevant auth events into the audit trail (PRD 5.9:
 * login/security events are first-class audit records).
 */
final class AuditEventsSubscriber
{
    public function subscribe(mixed $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            PasswordReset::class => 'onPasswordReset',
        ];
    }

    public function onLogin(Login $event): void
    {
        $this->audit('auth.login', $event);
    }

    public function onLogout(Logout $event): void
    {
        $this->audit('auth.logout', $event);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        $this->audit('auth.password_reset', $event);
    }

    private function audit(string $action, object $event): void
    {
        $user = $event->user ?? null;

        if (! $user instanceof User) {
            return;
        }

        app(Auditing::class)->record($action, subject: $user, actor: $user);
    }
}
