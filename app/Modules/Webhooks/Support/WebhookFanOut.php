<?php

namespace App\Modules\Webhooks\Support;

use App\Models\User;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Illuminate\Auth\Events\Registered;

/**
 * Fan-out of platform events to outbound webhooks (user.created).
 * Registered after the tenancy bootstrap listeners so the personal
 * organization already exists when the payload is built.
 */
final class WebhookFanOut
{
    public function onRegistered(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $organization = $user->personalOrganization();

        if ($organization === null) {
            return;
        }

        app(WebhookDispatcher::class)->dispatch($organization, 'user.created', [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }
}
