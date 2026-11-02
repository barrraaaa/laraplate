<?php

namespace App\Modules\Tenancy\Services;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Exceptions\LastOwnerRemovalBlockedException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single write-path for membership changes.
 *
 * The membership row (role enum) is the curated source of truth; every
 * mutation mirrors to spatie/laravel-permission so granular
 * `$user->can('…')` checks stay organization-scoped and consistent.
 */
final class MembershipService
{
    public function join(Organization $organization, User $user, OrganizationRole $role): Membership
    {
        return DB::transaction(function () use ($organization, $user, $role): Membership {
            $membership = Membership::updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $user->id],
                ['role' => $role->value],
            );

            $this->syncPermissions($membership);

            return $membership;
        });
    }

    public function changeRole(Membership $membership, OrganizationRole $role): Membership
    {
        return DB::transaction(function () use ($membership, $role): Membership {
            $this->guardRoleChange($membership, $role);

            $membership->update(['role' => $role->value]);
            $this->syncPermissions($membership);

            return $membership;
        });
    }

    /**
     * @throws LastOwnerRemovalBlockedException
     */
    public function remove(Membership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            if ($membership->isOwner() && $this->countOwners($membership->organization) <= 1) {
                throw new LastOwnerRemovalBlockedException;
            }

            $membership->delete();
            $this->forgetPermissions($membership);
        });
    }

    /**
     * Ownership transfer (PRD 5.3): the current owner hands the Owner role
     * to another active member of the same organization.
     *
     * @throws InvalidArgumentException when the target is not a member of the organization.
     */
    public function transferOwnership(Organization $organization, User $newOwner): void
    {
        DB::transaction(function () use ($organization, $newOwner): void {
            $targetMembership = Membership::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $newOwner->id)
                ->first();

            if ($targetMembership === null) {
                throw new InvalidArgumentException(
                    'The new owner must be an active member of the organization.'
                );
            }

            // Demote every current owner to Admin (target included when
            // applicable) and keep their permission sets in sync.
            $owners = Membership::query()
                ->where('organization_id', $organization->id)
                ->where('role', OrganizationRole::Owner->value)
                ->get();

            foreach ($owners as $owner) {
                $owner->update(['role' => OrganizationRole::Admin->value]);
                $this->syncPermissions($owner);
            }

            $this->changeRole($targetMembership->fresh(), OrganizationRole::Owner);
        });
    }

    /**
     * Mirror the membership role into spatie (organization-scoped role
     * assignment). Roles of other organizations are never touched.
     *
     * Note for long-running processes: Eloquent caches spatie's `roles`
     * relation on the model instance, so code that flips the permissions
     * team mid-process must re-hydrate the model (fresh()) after switching.
     */
    public function syncPermissions(Membership $membership): void
    {
        // Team context drives spatie's team-scoped role resolution — set it
        // before any role materialization so the role is created under the
        // right organization.
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($membership->organization_id);

        $roleName = self::spatieRoleFor($membership->role);
        $this->ensureRole($roleName);

        // Materialized records must be visible to the registrar whose
        // in-memory cache may predate them, then lock the team context
        // back in for the assignment itself.
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId($membership->organization_id);

        $membership->user->syncRoles([$roleName]);
    }

    /**
     * Self-healing role bootstrap: registration flow must never break on a
     * fresh database just because a seeder wasn't run. Materializes the
     * role + permission set from config/saas.php -> roles when missing
     * (idempotent; the seeder remains the documented explicit path).
     *
     * With spatie "teams" enabled, role definitions are team-scoped too —
     * the role row is created under the registrar's active team.
     */
    private function ensureRole(string $roleName): void
    {
        $permissions = collect((array) config("saas.roles.{$roleName}", []))
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
    }

    public function forgetPermissions(Membership $membership): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($membership->organization_id);

        $membership->user->syncRoles([]);
    }

    private function countOwners(Organization $organization): int
    {
        return Membership::query()
            ->where('organization_id', $organization->id)
            ->where('role', OrganizationRole::Owner->value)
            ->count();
    }

    private function guardRoleChange(Membership $membership, OrganizationRole $role): void
    {
        // Demoting an owner who is the last owner would strand the org.
        if ($membership->isOwner() && $role !== OrganizationRole::Owner && $this->countOwners($membership->organization) <= 1) {
            throw new LastOwnerRemovalBlockedException;
        }
    }

    /**
     * Maps the curated baseline role to its spatie counterpart
     * (config/saas.php -> roles). Unknown memberships fail loudly —
     * a new role means the permission catalog must be extended.
     */
    private static function spatieRoleFor(OrganizationRole $role): string
    {
        $name = 'organization-'.$role->value;

        throw_unless(
            array_key_exists($name, (array) config('saas.roles', [])),
            InvalidArgumentException::class,
            "No role permissions defined for [{$name}] in config/saas.php -> roles.",
        );

        return $name;
    }
}
