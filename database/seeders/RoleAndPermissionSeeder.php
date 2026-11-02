<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the granular permission catalog (config/saas.php -> roles) into
 * spatie/laravel-permission. Role definitions are global (no team);
 * assignments become organization-scoped via the organization_id team
 * column when MembershipService syncs a membership.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<int, string>> $rolePermissions */
        $rolePermissions = (array) config('saas.roles', []);

        $permissionNames = collect($rolePermissions)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $existingPermissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('name');

        foreach ($permissionNames as $permissionName) {
            if (! $existingPermissions->contains($permissionName)) {
                Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissionsForRole);
        }
    }
}
