<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Modules\Entitlements\Services\EntitlementService;
use App\Modules\Projects\Http\Requests\StoreProjectRequest;
use App\Modules\Projects\Http\Requests\UpdateProjectRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\ProjectService;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Example module controller (PRD 5.12) — the reference slice:
 * Inertia pages under pages/app/Projects, policies per action, quota-
 * aware writes through the service, tenant context from the middleware.
 */
class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ProjectService $projects,
        private EntitlementService $entitlements,
    ) {}

    public function index(): Response
    {
        $organization = OrganizationContext::organization();

        return Inertia::render('app/Projects/Index', [
            'projects' => Project::query()
                ->with('creator:id,name')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Project $project) => $this->serialize($project)),
            'usage' => [
                'used' => Project::query()->where('organization_id', $organization->id)->count(),
                'limit' => $this->entitlements->quota($organization, 'projects.max'),
            ],
            'canCreate' => request()->user()->isOrganizationAdmin($organization),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $organization = OrganizationContext::organization();

        if (! $request->user()->isOrganizationAdmin($organization)) {
            abort(403);
        }

        $this->projects->create($organization, $request->validated());

        return redirect()
            ->route('projects.index')
            ->with('status', 'project-created');
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->projects->update($project, $request->validated());

        return back()->with('status', 'project-updated');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $this->projects->destroy($project);

        return back()->with('status', 'project-deleted');
    }

    /**
     * @return array{id: int, name: string, description: string|null, status: string, creator: string|null, createdAt: string|null}
     */
    private function serialize(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status->value,
            'creator' => $project->creator?->name,
            'createdAt' => $project->created_at?->toIso8601String(),
        ];
    }
}
