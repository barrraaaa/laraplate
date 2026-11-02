<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('saas.mode', 'team');
    }

    public function test_non_members_cannot_open_an_organizations_settings_page(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Private Co']);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('organizations.show', $organization))
            ->assertForbidden();
    }

    public function test_non_members_cannot_switch_context_into_an_organization(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Private Co']);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('organizations.switch', $organization))
            ->assertForbidden();

        $this->assertNull(session('current_organization_id'));
    }

    public function test_each_organization_keeps_its_own_membership_isolated(): void
    {
        $user = User::factory()->create();
        $alpha = $this->organizationFor($user);
        $beta = $this->organizationFor($user);

        $this->assertCount(1, $alpha->users()->where('users.id', $user->id)->get());
        $this->assertCount(1, $beta->users()->where('users.id', $user->id)->get());
        $this->assertSame(2, $user->organizations()->count());
    }

    private function organizationFor(User $user): Organization
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Acme '.fake()->uuid()]);

        app(MembershipService::class)->join($organization, $user, OrganizationRole::Member);

        return $organization;
    }
}
