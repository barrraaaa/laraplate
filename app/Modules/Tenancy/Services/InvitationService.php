<?php

namespace App\Modules\Tenancy\Services;

use App\Mail\Tenancy\InvitationMail;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Services\Auditing;
use App\Modules\Tenancy\Enums\OrganizationRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Email invitation lifecycle (PRD 5.3): invite, accept, decline, expire,
 * resend, revoke. Tokens are hashed at rest; expiry is configurable via
 * config/saas.php.
 */
final class InvitationService
{
    public function __construct(
        private MembershipService $memberships,
        private Auditing $auditing,
    ) {}

    public function invite(Organization $organization, string $email, OrganizationRole $role, User $inviter): Invitation
    {
        return DB::transaction(function () use ($organization, $email, $role, $inviter): Invitation {
            $this->guardNotAlreadyMember($organization, $email);

            $token = Str::random(64);

            $invitation = Invitation::updateOrCreate(
                ['organization_id' => $organization->id, 'email' => $email],
                [
                    'role' => $role->value,
                    'token_hash' => hash('sha256', $token),
                    'invited_by' => $inviter->id,
                    'expires_at' => now()->addDays($this->expiryDays()),
                    'accepted_at' => null,
                    'declined_at' => null,
                    'revoked_at' => null,
                ],
            );

            Mail::to($email)->queue(new InvitationMail($invitation, $token));

            $invitation->forceFill(['sent_at' => now()])->save();

            $this->auditing->record('organization.invitation_sent', $organization, $invitation, [
                'email' => $invitation->email,
                'role' => $invitation->role->value,
            ]);

            return $invitation;
        });
    }

    public function resend(Invitation $invitation): Invitation
    {
        $token = Str::random(64);

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays($this->expiryDays()),
            'sent_at' => now(),
        ])->save();

        Mail::to($invitation->email)->queue(new InvitationMail($invitation, $token));

        return $invitation;
    }

    /**
     * Accepts an invitation on behalf of the authenticated user.
     *
     * @throws RuntimeException when the invitation is not pending.
     */
    public function accept(Invitation $invitation, User $user): Membership
    {
        return DB::transaction(function () use ($invitation, $user): Membership {
            throw_unless($invitation->isPending(), RuntimeException::class, 'This invitation is no longer valid.');

            $membership = $this->memberships->join($invitation->organization, $user, $invitation->role);

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ])->save();

            $this->auditing->record('organization.invitation_accepted', $invitation->organization, $user, [
                'email' => $invitation->email,
                'role' => $invitation->role->value,
            ]);

            return $membership;
        });
    }

    public function decline(Invitation $invitation): void
    {
        throw_unless($invitation->isPending(), RuntimeException::class, 'This invitation is no longer valid.');

        $invitation->forceFill(['declined_at' => now()])->save();

        $this->auditing->record('organization.invitation_declined', $invitation->organization, $invitation, [
            'email' => $invitation->email,
        ]);
    }

    public function revoke(Invitation $invitation): void
    {
        throw_unless($invitation->isPending(), RuntimeException::class, 'This invitation is no longer valid.');

        $invitation->forceFill(['revoked_at' => now()])->save();

        $this->auditing->record('organization.invitation_revoked', $invitation->organization, $invitation, [
            'email' => $invitation->email,
        ]);
    }

    public function findByToken(string $token): ?Invitation
    {
        return Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    /**
     * Auto-accepts the invitation targeted at this email address after a
     * fresh registration — token stored in the session during the pre
     * -register acceptance flow.
     */
    public function acceptForEmailAfterRegistration(string $email, User $user): bool
    {
        $token = session()->pull('pending_invitation_token');

        if ($token === null) {
            return false;
        }

        $invitation = $this->findByToken($token);

        if ($invitation === null || ! $invitation->isPending()) {
            return false;
        }

        if (Str::lower($invitation->email) !== Str::lower($email)) {
            return false;
        }

        $this->accept($invitation, $user);

        return true;
    }

    private function guardNotAlreadyMember(Organization $organization, string $email): void
    {
        $alreadyMember = $organization->users()
            ->where('email', $email)
            ->exists();

        throw_if(
            $alreadyMember,
            InvalidArgumentException::class,
            "{$email} is already a member of this organization.",
        );
    }

    private function expiryDays(): int
    {
        return (int) config('saas.invitations.expiry_days', 3);
    }
}
