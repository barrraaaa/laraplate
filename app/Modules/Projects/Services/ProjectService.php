<?php

namespace App\Modules\Projects\Services;

use App\Models\Organization;
use App\Modules\Core\FeatureManager;
use App\Modules\Entitlements\Exceptions\QuotaExceededException;
use App\Modules\Entitlements\Services\EntitlementService;
use App\Modules\Projects\Models\Project;

/**
 * Example module service — the place where quota-bearing writes are
 * enforced. Controllers stay thin; this is the "copy me" pattern the
 * documentation references.
 */
final class ProjectService
{
    public function __construct(
        private EntitlementService $entitlements,
        private FeatureManager $features,
    ) {}

    /**
     * Create a project when the plan's projects.max quota allows it.
     *
     * This example counts table rows against the quota (row-based
     * entitlement); event-style consumption (storage, API calls…) uses
     * EntitlementService::record() instead — both read the same plan
     * catalog.
     *
     * @param  array{name: string, description?: string|null, status?: string}  $data
     *
     * @throws QuotaExceededException
     */
    public function create(Organization $organization, array $data): Project
    {
        if ($this->features->enabled('usage_limits')) {
            $quota = $this->entitlements->quota($organization, 'projects.max');
            $count = Project::query()->where('organization_id', $organization->id)->count();

            if ($quota !== null && $count >= $quota) {
                throw new QuotaExceededException('projects.max');
            }
        }

        return Project::query()->create([
            'organization_id' => $organization->id,
            'created_by' => auth()->id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * @param  array{name: string, description?: string|null, status?: string}  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $project->description,
            'status' => $data['status'] ?? $project->status->value,
        ]);

        return $project;
    }

    public function destroy(Project $project): void
    {
        $project->delete();
    }
}
