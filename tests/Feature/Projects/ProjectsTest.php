<?php

namespace Tests\Feature\Projects;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD 5.12: the example module demonstrates the extension pattern —
 * CRUD, policies, tenancy scoping and quota enforcement.
 */
class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Projects Co']);
    }

    public function test_owner_can_create_a_project_inside_their_organization(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('projects.store'), ['name' => 'Launch site'])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'organization_id' => $this->organization->id,
            'name' => 'Launch site',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_projects_are_scoped_to_the_active_organization(): void
    {
        $otherOwner = User::factory()->create();
        $otherOrganization = app(OrganizationService::class)
            ->createTeamOrganization($otherOwner, ['name' => 'Other Co']);

        Project::query()->create([
            'organization_id' => $otherOrganization->id,
            'created_by' => $otherOwner->id,
            'name' => 'Foreign project',
        ]);

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get(route('projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects', 0));
    }

    public function test_free_plan_blocks_creating_beyond_the_projects_quota(): void
    {
        Project::query()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->owner->id,
            'name' => 'One',
        ]);
        Project::query()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->owner->id,
            'name' => 'Two',
        ]);
        Project::query()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->owner->id,
            'name' => 'Three', // free plan allows 3
        ]);

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->from(route('projects.index'))
            ->post(route('projects.store'), ['name' => 'Four'])
            ->assertSessionHasErrors('name');
    }

    public function test_member_without_admin_role_cannot_create_projects(): void
    {
        $member = User::factory()->create();
        app(MembershipService::class)->join($this->organization, $member, OrganizationRole::Member);

        $this->actingAs($member)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('projects.store'), ['name' => 'Nope'])
            ->assertForbidden();
    }
}
