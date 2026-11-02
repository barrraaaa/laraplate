<?php

namespace Tests\Feature\Tenancy;

use App\Mail\Tenancy\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationsTest extends TestCase
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
            ->createTeamOrganization($this->owner, ['name' => 'Acme Invites']);
    }

    public function test_admin_can_invite_a_member_by_email(): void
    {
        Mail::fake();

        $this->actingAs($this->owner)
            ->post(route('organizations.invitations.store', $this->organization), [
                'email' => 'new@example.com',
                'role' => 'member',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'invitation-sent');

        Mail::assertQueued(InvitationMail::class, 1);

        $invitation = Invitation::query()
            ->where('organization_id', $this->organization->id)
            ->where('email', 'new@example.com')
            ->firstOrFail();

        $this->assertTrue($invitation->isPending());
        $this->assertSame(OrganizationRole::Member, $invitation->role);
        $this->assertSame($this->owner->id, $invitation->invited_by);
    }

    public function test_inviting_an_existing_member_fails_with_validation_error(): void
    {
        $member = User::factory()->create();
        app(MembershipService::class)->join($this->organization, $member, OrganizationRole::Member);

        $this->actingAs($this->owner)
            ->from(route('organizations.show', $this->organization))
            ->post(route('organizations.invitations.store', $this->organization), [
                'email' => $member->email,
                'role' => 'member',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_inviting_as_owner_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->from(route('organizations.show', $this->organization))
            ->post(route('organizations.invitations.store', $this->organization), [
                'email' => 'new@example.com',
                'role' => 'owner',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_invitee_can_accept_and_joins_with_the_invited_role(): void
    {
        $invitee = User::factory()->create();
        [$invitation, $token] = $this->pendingInvitation($invitee->email, OrganizationRole::Admin);

        $this->actingAs($invitee)
            ->get('/invitations/'.$token)
            ->assertRedirect(route('dashboard'));

        $this->assertSame(
            OrganizationRole::Admin,
            $invitee->organizationRole($this->organization),
        );

        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $invitee = User::factory()->create();
        [$invitation, $token] = $this->pendingInvitation($invitee->email, OrganizationRole::Member);
        $invitation->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->actingAs($invitee)
            ->get('/invitations/'.$token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('app/Invitations/Invalid'));

        $this->assertNull($invitee->organizationRole($this->organization));
    }

    public function test_new_user_registration_through_an_invite_link_auto_accepts(): void
    {
        [, $token] = $this->pendingInvitation('fresh@example.com', OrganizationRole::Member);

        $this->get('/invitations/'.$token)
            ->assertRedirect(route('register'));

        $this->post('/register', [
            'name' => 'Fresh Invitee',
            'email' => 'fresh@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $freshUser = User::query()->where('email', 'fresh@example.com')->firstOrFail();

        $this->assertSame(OrganizationRole::Member, $freshUser->organizationRole($this->organization));
    }

    public function test_admin_can_revoke_a_pending_invitation(): void
    {
        [, $token] = $this->pendingInvitation('victim@example.com', OrganizationRole::Member);

        $this->actingAs($this->owner)
            ->delete(route('organizations.invitations.destroy', [
                $this->organization,
                $this->invitationByToken($token),
            ]))
            ->assertRedirect();

        $this->assertFalse($this->invitationByToken($token)->fresh()->isPending());
    }

    /** @return array{0: Invitation, 1: string} */
    private function pendingInvitation(string $email, OrganizationRole $role): array
    {
        $token = Str::random(64);

        $invitation = Invitation::create([
            'organization_id' => $this->organization->id,
            'email' => $email,
            'role' => $role->value,
            'token_hash' => hash('sha256', $token),
            'invited_by' => $this->owner->id,
            'expires_at' => now()->addDays(3),
            'sent_at' => now(),
        ]);

        return [$invitation, $token];
    }

    private function invitationByToken(string $token): Invitation
    {
        return Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
