<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD 5.9: security-relevant actions land in the audit trail with
 * actor + tenant context.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_audited(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'auth.login',
            'actor_id' => $user->id,
        ]);
    }

    public function test_membership_changes_are_audited(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Audited Co']);

        $member = User::factory()->create();

        // The membership write happens inside an authenticated request in
        // production — reproduce that context for the audit's actor.
        $this->actingAs($owner);
        app(MembershipService::class)->join($organization, $member, OrganizationRole::Member);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'organization.member_joined',
            'organization_id' => $organization->id,
            'actor_id' => $owner->id,
        ]);
    }

    public function test_organization_creation_is_audited(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner);
        app(OrganizationService::class)->createTeamOrganization($owner, ['name' => 'Created Co']);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'organization.created',
            'actor_id' => $owner->id,
        ]);
    }

    public function test_account_deletion_is_audited_with_actor_metadata(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $event = AuditEvent::query()
            ->where('action', 'auth.account_deleted')
            ->firstOrFail();

        // The FK nulls on user delete; the point-in-time actor metadata
        // keeps the trail readable.
        $this->assertSame($user->email, $event->metadata['actor_email']);
    }
}
