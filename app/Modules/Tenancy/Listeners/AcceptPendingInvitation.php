<?php

namespace App\Modules\Tenancy\Listeners;

use App\Models\User;
use App\Modules\Tenancy\Services\InvitationService;
use Illuminate\Auth\Events\Registered;

/**
 * Completes the purchase of an invitation when a brand-new user registers
 * through an invite link: the invitation token was parked in the session
 * (see invitations.accept route) before registration.
 */
final class AcceptPendingInvitation
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        app(InvitationService::class)->acceptForEmailAfterRegistration($event->user->email, $event->user);
    }
}
