<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Http\Requests\StoreInvitationRequest;
use App\Modules\Tenancy\Services\InvitationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Email invitation lifecycle (PRD 5.3): invite, resend, revoke, accept,
 * decline. Acceptance supports both signed-in users and new signups
 * (token parked in the session until registration completes).
 */
class InvitationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private InvitationService $invitations) {}

    public function store(StoreInvitationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        try {
            $this->invitations->invite(
                $organization,
                $request->string('email')->toString(),
                OrganizationRole::from($request->string('role')->toString()),
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', 'invitation-sent');
    }

    public function resend(Organization $organization, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->organization_id === $organization->id, 404);

        $this->authorize('manageMembers', $organization);

        $this->invitations->resend($invitation);

        return back()->with('status', 'invitation-resent');
    }

    public function destroy(Organization $organization, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->organization_id === $organization->id, 404);

        $this->authorize('manageMembers', $organization);

        $this->invitations->revoke($invitation);

        return back()->with('status', 'invitation-revoked');
    }

    /**
     * Landing point for the signed link in the invitation email.
     * Guests are routed through registration with the token parked in the
     * session; signed-in users accept immediately.
     */
    public function accept(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = $this->invitations->findByToken($token);

        if ($invitation === null || ! $invitation->isPending()) {
            return Inertia::render('app/Invitations/Invalid', [
                'reason' => $invitation?->isExpired() === true ? 'expired' : 'invalid',
            ]);
        }

        if (auth()->guest()) {
            $request->session()->put('pending_invitation_token', $token);

            return redirect()->route('register');
        }

        $this->invitations->accept($invitation, $request->user());

        return redirect()->route('dashboard')->with('status', 'invitation-accepted');
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitations->findByToken($token);

        if ($invitation !== null && $invitation->isPending() && $request->user()?->email === $invitation->email) {
            $this->invitations->decline($invitation);
        }

        return redirect()->route('dashboard')->with('status', 'invitation-declined');
    }
}
