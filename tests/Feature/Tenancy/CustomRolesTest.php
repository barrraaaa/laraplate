<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PRD 5.4 P1: custom organization roles composed from the permission
 * catalog, assignable by owners/admins.
 */
class CustomRolesTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('saas.mode', 'team');

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Roles Co']);
    }

    public function test_admin_can_create_a_custom_role(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('organizations.roles.store', $this->organization), [
                'name' => 'Content editor',
                'permissions' => ['projects.view', 'projects.create'],
            ])
            ->assertRedirect();

        $role = Role::query()
            ->where('organization_id', $this->organization->id)
            ->where('name', 'custom-content-editor')
            ->firstOrFail();

        $this->assertSame(
            ['projects.create', 'projects.view'],
            $role->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_custom_role_grants_its_permissions_to_an_assigned_member(): void
    {
        $member = User::factory()->create();
        $membership = app(MembershipService::class)
            ->join($this->organization, $member, OrganizationRole::Member);

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('organizations.roles.store', $this->organization), [
                'name' => 'Project manager',
                'permissions' => ['billing.manage'],
            ])
            ->assertRedirect();

        $role = Role::query()
            ->where('organization_id', $this->organization->id)
            ->where('name', 'custom-project-manager')
            ->firstOrFail();

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('organizations.members.assign-role', [$this->organization, $membership]), [
                'role_id' => $role->id,
            ])
            ->assertRedirect();

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $member = $member->fresh();

        $this->assertTrue($member->hasPermissionTo('billing.manage'));
        $this->assertFalse($member->hasPermissionTo('members.manage'));
    }

    public function test_role_assignment_can_be_reset_to_baseline(): void
    {
        $member = User::factory()->create();
        $membership = app(MembershipService::class)
            ->join($this->organization, $member, OrganizationRole::Admin);

        app(MembershipService::class)->assignCustomRole(
            $membership,
            Role::create(['name' => 'custom-x', 'guard_name' => 'web', 'organization_id' => $this->organization->id]),
        );

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('organizations.members.assign-role', [$this->organization, $membership]), [
                'role_id' => null,
            ])
            ->assertRedirect();

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($member->fresh()->hasPermissionTo('members.manage'));
    }

    public function test_assigned_role_cannot_be_deleted(): void
    {
        $member = User::factory()->create();
        $membership = app(MembershipService::class)
            ->join($this->organization, $member, OrganizationRole::Member);

        $role = Role::create(['name' => 'custom-temp', 'guard_name' => 'web', 'organization_id' => $this->organization->id]);
        app(MembershipService::class)->assignCustomRole($membership, $role);

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->delete(route('organizations.roles.destroy', [$this->organization, $role]))
            ->assertSessionHasErrors('role');

        $this->assertNotNull($role->fresh());
    }

    public function test_member_without_admin_role_cannot_manage_roles(): void
    {
        $member = User::factory()->create();
        app(MembershipService::class)->join($this->organization, $member, OrganizationRole::Member);

        $this->actingAs($member)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get(route('organizations.roles.index', $this->organization))
            ->assertForbidden();
    }
}
