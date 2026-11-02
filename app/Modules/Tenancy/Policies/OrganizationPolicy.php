<?php

namespace App\Modules\Tenancy\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;

/**
 * Organization authorization (PRD 5.4). Baseline role hierarchy:
 * Owner > Admin > Member > Viewer. Granular capabilities per role live in
 * config/saas.php -> roles; these methods encode the curated hierarchy.
 */
class OrganizationPolicy
{
    /**
     * Any member can see their organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationRole($organization, ...self::anyRole());
    }

    /**
     * Manage settings — owners and admins.
     */
    public function manage(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Manage members — owners and admins.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Manage billing (plans, checkout, cancellation) — owners and admins.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Ownership transfer — owner only.
     */
    public function transfer(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationRole($organization, OrganizationRole::Owner);
    }

    /**
     * Archive — owner only, and never a personal organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return ! $organization->isPersonal()
            && $user->hasOrganizationRole($organization, OrganizationRole::Owner);
    }

    /**
     * @return array<int, OrganizationRole>
     */
    private static function anyRole(): array
    {
        return [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member,
            OrganizationRole::Viewer,
        ];
    }
}
