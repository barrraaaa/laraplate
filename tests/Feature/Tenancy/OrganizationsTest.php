<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('saas.mode', 'team');
    }

    public function test_user_can_create_an_organization_and_becomes_its_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Acme Corp',
        ]);

        $response->assertRedirect();

        $organization = Organization::query()->where('name', 'Acme Corp')->firstOrFail();

        $this->assertFalse($organization->isPersonal());
        $this->assertSame(OrganizationRole::Owner, $user->organizationRole($organization));
    }

    public function test_organization_routes_are_hidden_in_single_mode(): void
    {
        config()->set('saas.mode', 'single');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.index'))
            ->assertNotFound();
    }

    public function test_admin_can_update_the_organization(): void
    {
        [$organization, $admin] = $this->organizationWithMember(OrganizationRole::Admin);

        $this->actingAs($admin)
            ->patch(route('organizations.update', $organization), ['name' => 'Renamed Ltd'])
            ->assertRedirect();

        $this->assertSame('Renamed Ltd', $organization->fresh()->name);
    }

    public function test_member_cannot_update_or_archive_the_organization(): void
    {
        [$organization, $member] = $this->organizationWithMember(OrganizationRole::Member);

        $this->actingAs($member)
            ->patch(route('organizations.update', $organization), ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('organizations.destroy', $organization))
            ->assertForbidden();
    }

    public function test_owner_can_archive_a_shared_organization(): void
    {
        [$organization, $owner] = $this->organizationWithMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('organizations.index'));

        $this->assertSoftDeleted($organization);
    }

    public function test_personal_organization_cannot_be_archived(): void
    {
        $user = User::factory()->create();
        $personal = app(OrganizationService::class)->createPersonalOrganization($user);

        $this->actingAs($user)
            ->delete(route('organizations.destroy', $personal))
            ->assertForbidden();

        $this->assertNotSoftDeleted($personal);
    }

    public function test_context_switching_updates_the_active_organization(): void
    {
        [$organization, $member] = $this->organizationWithMember(OrganizationRole::Member);

        $this->actingAs($member)
            ->post(route('organizations.switch', $organization))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($organization->id, session('current_organization_id'));
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function organizationWithMember(OrganizationRole $role): array
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)->createTeamOrganization($owner, ['name' => 'Acme '.fake()->uuid()]);

        $member = User::factory()->create();
        app(MembershipService::class)->join($organization, $member, $role);

        return [$organization, $role === OrganizationRole::Owner ? $owner : $member, $owner];
    }
}
