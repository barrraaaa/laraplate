<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Http\Requests\TransferOwnershipRequest;
use App\Modules\Tenancy\Http\Requests\UpdateMemberRequest;
use App\Modules\Tenancy\Services\MembershipService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Membership management (PRD 5.3): role changes, removals and ownership
 * transfer, all guarded by last-owner protection.
 */
class MemberController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private MembershipService $memberships) {}

    public function update(UpdateMemberRequest $request, Organization $organization, Membership $member): RedirectResponse
    {
        abort_unless($member->organization_id === $organization->id, 404);

        $this->authorize('manageMembers', $organization);

        $role = OrganizationRole::from($request->string('role')->toString());

        // Only the owner may grant the Owner role (i.e. via transfer).
        if ($role === OrganizationRole::Owner) {
            $this->authorize('transfer', $organization);
        }

        $this->memberships->changeRole($member, $role);

        return back()->with('status', 'member-updated');
    }

    public function destroy(Organization $organization, Membership $member): RedirectResponse
    {
        abort_unless($member->organization_id === $organization->id, 404);

        $this->authorize('manageMembers', $organization);

        $this->memberships->remove($member);

        return back()->with('status', 'member-removed');
    }

    public function transferOwnership(TransferOwnershipRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('transfer', $organization);

        $newOwner = User::query()->findOrFail($request->integer('user_id'));

        $this->memberships->transferOwnership($organization, $newOwner);

        return back()->with('status', 'ownership-transferred');
    }
}
