<?php

namespace App\Modules\Tenancy\Listeners;

use App\Models\User;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Auth\Events\Registered;

/**
 * Bootstraps the invisible personal organization every user needs,
 * regardless of application mode (PRD 6.1: single-user journeys proceed
 * without creating an organization).
 */
final class CreatePersonalOrganization
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        app(OrganizationService::class)->createPersonalOrganization($event->user);
    }
}
