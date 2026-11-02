<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Models\Membership;
use App\Models\Organization;
use App\Modules\Audit\Services\Auditing;
use App\Modules\Tenancy\Services\MembershipService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Custom organization roles (PRD 5.4 P1): owners/admins define roles from
 * the permission catalog and assign them to members. Baseline roles stay
 * read-only and config-driven.
 */
class OrganizationRoleController extends Controller
{
    use AuthorizesRequests;

    private const BASELINE = ['organization-owner', 'organization-admin', 'organization-member', 'organization-viewer'];

    public function __construct(private MembershipService $memberships) {}

    public function index(Request $request, Organization $organization): Response
    {
        $this->authorize('manageMembers', $organization);

        // Work inside the org's team context for listing.
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $roles = Role::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('app/Organizations/Roles', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'baseline' => in_array($role->name, self::BASELINE, true),
                'permissions' => $role->permissions()->pluck('name')->values(),
                'assignedTo' => Membership::query()
                    ->where('organization_id', $organization->id)
                    ->where('custom_role_id', $role->id)
                    ->count(),
            ])->values(),
            'permissionCatalog' => collect((array) config('saas.roles', []))
                ->flatten()
                ->unique()
                ->sort()
                ->values(),
            'members' => $organization->memberships()
                ->with('user:id,name,email')
                ->get()
                ->map(fn (Membership $membership) => [
                    'id' => $membership->id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'customRoleId' => $membership->custom_role_id,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $roleName = str($data['name'])->slug('-')->prepend('custom-')->toString();

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->firstOrCreate(
            [
                'name' => $roleName,
                'guard_name' => 'web',
                'organization_id' => $organization->id,
            ],
        );

        $permissions = collect($data['permissions'] ?? [])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'))
            ->values();

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditing()->record('organization.custom_role_created', $organization, $role, [
            'name' => $roleName,
        ]);

        return back()->with('status', 'role-created');
    }

    public function update(Request $request, Organization $organization, Role $role): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        abort_unless((int) $role->getAttribute('organization_id') === (int) $organization->id, 404);

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($data['permissions'] ?? [])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'))
            ->values();

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditing()->record('organization.custom_role_updated', $organization, $role);

        return back()->with('status', 'role-updated');
    }

    public function destroy(Organization $organization, Role $role): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        abort_unless((int) $role->getAttribute('organization_id') === (int) $organization->id, 404);
        abort_if(in_array($role->name, self::BASELINE, true), 403, 'Baseline roles cannot be deleted.');

        $assigned = Membership::query()
            ->where('organization_id', $organization->id)
            ->where('custom_role_id', $role->id)
            ->exists();

        if ($assigned) {
            return back()->withErrors(['role' => 'The role is still assigned to members — unassign them first.']);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditing()->record('organization.custom_role_deleted', $organization, $role);

        return back()->with('status', 'role-deleted');
    }

    public function assign(Request $request, Organization $organization, Membership $member): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        abort_unless($member->organization_id === $organization->id, 404);

        $data = $request->validate([
            'role_id' => ['nullable', 'integer'],
        ]);

        if (empty($data['role_id'])) {
            $this->memberships->clearCustomRole($member);

            return back()->with('status', 'member-role-reset');
        }

        $role = Role::query()->findOrFail($data['role_id']);

        $this->memberships->assignCustomRole($member, $role);

        return back()->with('status', 'member-role-assigned');
    }

    private function auditing(): Auditing
    {
        return app(Auditing::class);
    }
}
