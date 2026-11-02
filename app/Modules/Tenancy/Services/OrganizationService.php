<?php

namespace App\Modules\Tenancy\Services;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Organization lifecycle operations.
 */
final class OrganizationService
{
    /**
     * The invisible workspace behind "single-user mode": one per user,
     * owned by the user, never surfaced by the UI in single mode.
     */
    public function createPersonalOrganization(User $user): Organization
    {
        return DB::transaction(function () use ($user): Organization {
            $organization = Organization::create([
                'name' => $user->name,
                'slug' => $this->uniquePersonalSlug($user),
                'personal' => true,
                'created_by' => $user->id,
            ]);

            $organization->memberships()->create([
                'user_id' => $user->id,
                'role' => OrganizationRole::Owner,
            ]);

            return $organization;
        });
    }

    /**
     * Find-or-create the user's personal organization (invariant repair;
     * safe to call repeatedly).
     */
    public function personalOrganizationFor(User $user): Organization
    {
        return $user->personalOrganization() ?? $this->createPersonalOrganization($user);
    }

    private function uniquePersonalSlug(User $user): string
    {
        return Str::slug($user->name).'-'.$user->id;
    }
}
