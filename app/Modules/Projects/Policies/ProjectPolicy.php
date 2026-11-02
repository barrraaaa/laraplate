<?php

namespace App\Modules\Projects\Policies;

use App\Models\User;
use App\Modules\Projects\Models\Project;

/**
 * Example module policy (PRD 5.4/5.12). Registered manually via
 * Gate::policy in AppServiceProvider — module policies live outside the
 * auto-discovered App\Policies namespace.
 */
class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        return $user->isOrganizationAdmin($project->organization);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isOrganizationAdmin($project->organization);
    }
}
