<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MembershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('saas.mode', 'team');
    }

    public function test_membership_change_keeps_spatie_roles_in_sync_per_organization(): void
    {
        $user = User::factory()->create();
        $first = $this->organizationWithMember($user, OrganizationRole::Admin);
        $second = $this->organizationWithMember($user, OrganizationRole::Viewer);

        // Admin in the first org...
        app(PermissionRegistrar::class)->setPermissionsTeamId($first->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->hasPermissionTo('members.manage'));
        $this->assertTrue($user->hasPermissionTo('organizations.manage'));

        // ...but only a viewer in the second. The fresh() reload matters:
        // spatie's roles relation caches on the model instance, so a
        // long-running process must re-hydrate when flipping teams.
        app(PermissionRegistrar::class)->setPermissionsTeamId($second->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = $user->fresh();

        $this->assertFalse($user->hasPermissionTo('members.manage'));
        $this->assertTrue($user->hasPermissionTo('projects.view'));
    }

    public function test_member_cannot_manage_other_members(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $service = app(MembershipService::class);
        $memberMembership = $service->join($organization, $member, OrganizationRole::Member);
        $otherMembership = $service->join($organization, $other, OrganizationRole::Member);

        $this->actingAs($member)
            ->patch(route('organizations.members.update', [$organization, $otherMembership]), [
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('organizations.members.destroy', [$organization, $otherMembership]))
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('organizations.members.destroy', [$organization, $memberMembership]))
            ->assertForbidden();
    }

    public function test_admin_can_change_a_members_role(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $service = app(MembershipService::class);
        $member = $service->join($organization, User::factory()->create(), OrganizationRole::Viewer);

        $this->actingAs($owner)
            ->patch(route('organizations.members.update', [$organization, $member]), [
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Admin, $member->fresh()->role);
    }

    public function test_only_owners_can_grant_the_owner_role(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $service = app(MembershipService::class);
        $adminMembership = $service->join($organization, $admin, OrganizationRole::Admin);
        $targetMembership = $service->join($organization, $target, OrganizationRole::Member);

        $this->actingAs($admin)
            ->patch(route('organizations.members.update', [$organization, $targetMembership]), [
                'role' => 'owner',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_transfer_ownership_with_safeguards(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $successor = User::factory()->create();
        $service = app(MembershipService::class);
        $service->join($organization, $successor, OrganizationRole::Member);

        $this->actingAs($owner)
            ->post(route('organizations.transfer-ownership', $organization), [
                'user_id' => $successor->id,
            ])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Owner, $successor->organizationRole($organization));
        $this->assertSame(OrganizationRole::Admin, $owner->organizationRole($organization));
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $ownerMembership = $owner->memberships()
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('organizations.members.destroy', [$organization, $ownerMembership]))
            ->assertSessionHasErrors('role');
    }

    public function test_membership_id_from_another_organization_cannot_be_tampered_with(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $otherOrganization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Other Co']);
        $otherMembership = $owner->memberships()
            ->where('organization_id', $otherOrganization->id)
            ->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('organizations.members.update', [$organization, $otherMembership]), [
                'role' => 'viewer',
            ])
            ->assertNotFound();
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithOwner(): array
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Acme '.fake()->uuid()]);

        return [$organization, $owner];
    }

    private function organizationWithMember(User $user, OrganizationRole $role): Organization
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Acme '.fake()->uuid()]);

        app(MembershipService::class)->join($organization, $user, $role);

        return $organization;
    }
}
