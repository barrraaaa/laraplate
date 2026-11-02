<?php

namespace App\Modules\Tenancy\Listeners;

use App\Models\User;
use App\Notifications\Welcome;
use Illuminate\Auth\Events\Registered;

/**
 * Sends the queued welcome email after the personal workspace exists.
 */
final class WelcomeNewUser
{
    public function handle(Registered $event): void
    {
        if ($event->user instanceof User) {
            $event->user->notify(new Welcome);
        }
    }
}
