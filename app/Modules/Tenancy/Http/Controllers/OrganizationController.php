<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Models\Organization;
use App\Modules\Tenancy\Http\Requests\StoreOrganizationRequest;
use App\Modules\Tenancy\Http\Requests\UpdateOrganizationRequest;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared workspace (team organization) management — PRD 5.3.
 */
class OrganizationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private OrganizationService $organizations) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('app/Organizations/Index', [
            'organizations' => $user->organizations()
                ->where('personal', false)
                ->get(['organizations.id', 'organizations.name', 'organizations.slug']),
            'activeOrganizationId' => $request->session()->get('current_organization_id'),
            'personalOrganization' => $user->personalOrganization()?->only(['id', 'name']),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->organizations->createTeamOrganization($request->user(), $request->validated());

        $request->session()->put('current_organization_id', $organization->id);

        return redirect()->route('organizations.show', $organization)
            ->with('status', 'organization-created');
    }

    public function show(Request $request, Organization $organization): Response
    {
        $this->authorize('view', $organization);

        $memberships = $organization->memberships()
            ->with('user:id,name,email')
            ->orderBy('role')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('app/Organizations/Settings', [
            'organization' => $this->serialize($organization),
            'members' => $memberships->map(fn ($membership) => [
                'id' => $membership->id,
                'role' => $membership->role->value,
                'user' => [
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                ],
            ])->values(),
            'invitations' => $organization->invitations()
                ->whereNull('accepted_at')
                ->whereNull('declined_at')
                ->whereNull('revoked_at')
                ->get(['id', 'email', 'role', 'expires_at', 'sent_at'])
                ->map(fn ($invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'sent_at' => $invitation->sent_at?->toIso8601String(),
                ])->values(),
            'can' => [
                'manage' => $request->user()->can('manage', $organization),
                'manageMembers' => $request->user()->can('manageMembers', $organization),
                'transfer' => $request->user()->can('transfer', $organization),
                'delete' => $request->user()->can('delete', $organization),
            ],
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manage', $organization);

        $this->organizations->update($organization, $request->validated());

        return back()->with('status', 'organization-updated');
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $this->organizations->archive($organization);

        $request->session()->forget('current_organization_id');

        return redirect()->route('organizations.index')
            ->with('status', 'organization-archived');
    }

    /**
     * Context switching (PRD 5.3): any organization the user belongs to.
     */
    public function switchOrganizations(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('view', $organization);

        $request->session()->put('current_organization_id', $organization->id);

        return redirect()->route('dashboard');
    }

    /**
     * @return array{id: int, name: string, slug: string|null, personal: bool}
     */
    private function serialize(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'personal' => $organization->isPersonal(),
        ];
    }
}
