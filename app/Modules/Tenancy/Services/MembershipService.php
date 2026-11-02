<?php

namespace App\Modules\Tenancy\Services;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Services\Auditing;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Exceptions\LastOwnerRemovalBlockedException;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Auth;
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
    public function __construct(
        private Auditing $auditing,
        private WebhookDispatcher $webhooks,
    ) {}

    public function join(Organization $organization, User $user, OrganizationRole $role): Membership
    {
        return DB::transaction(function () use ($organization, $user, $role): Membership {
            $membership = Membership::updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $user->id],
                ['role' => $role->value],
            );

            $this->syncPermissions($membership);

            $this->auditing->record('organization.member_joined', $organization, $user, [
                'role' => $role->value,
            ]);

            $this->webhooks->dispatch($organization, 'organization.member_joined', [
                'user' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
                'role' => $role->value,
                'created_at' => now()->toIso8601String(),
            ]);

            return $membership;
        });
    }

    public function changeRole(Membership $membership, OrganizationRole $role): Membership
    {
        return DB::transaction(function () use ($membership, $role): Membership {
            $this->guardRoleChange($membership, $role);

            $membership->forceFill([
                'role' => $role->value,
                'custom_role_id' => null,
            ])->save();
            $this->syncPermissions($membership);

            $this->auditing->record('organization.member_role_changed', $membership->organization, $membership->user, [
                'role' => $role->value,
            ]);

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

            $this->auditing->record('organization.member_removed', $membership->organization, $membership->user, [
                'role' => $membership->role->value,
            ]);
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

            $this->auditing->record('organization.ownership_transferred', $organization, $newOwner, [
                'previous_owner_id' => $this->currentActorId(),
            ]);
        });
    }

    private function currentActorId(): ?int
    {
        return Auth::id();
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
        // Custom org roles (PRD 5.4 P1) override the baseline mirror.
        if ($membership->custom_role_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($membership->organization_id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            app(PermissionRegistrar::class)->setPermissionsTeamId($membership->organization_id);

            $customRole = Role::query()
                ->whereKey($membership->custom_role_id)
                ->where('organization_id', $membership->organization_id)
                ->first();

            if ($customRole !== null) {
                $membership->user->syncRoles([$customRole->name]);

                return;
            }

            // Custom role vanished — fall back to the baseline mirror.
            $membership->forceFill(['custom_role_id' => null])->save();
        }

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
     * Assign an organization-defined custom role (PRD 5.4 P1). The curated
     * membership role stays as the policy-hierarchy source; permissions
     * now resolve from the custom role.
     */
    public function assignCustomRole(Membership $membership, \Spatie\Permission\Contracts\Role $role): void
    {
        throw_unless(
            (int) $role->getAttribute('organization_id') === (int) $membership->organization_id,
            InvalidArgumentException::class,
            'The role must belong to the same organization.',
        );

        $membership->forceFill(['custom_role_id' => $role->id])->save();
        $this->syncPermissions($membership);
    }

    /**
     * Revert a membership to the baseline permission mirror.
     */
    public function clearCustomRole(Membership $membership): void
    {
        $membership->forceFill(['custom_role_id' => null])->save();
        $this->syncPermissions($membership);
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
