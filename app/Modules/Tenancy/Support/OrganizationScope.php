<?php

namespace App\Modules\Tenancy\Support;

use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts tenant-owned reads to the active organization.
 *
 * No-op when no organization context is resolved (guests, CLI, admin panel
 * override) — enforcement of the admin override lives in policies, not here.
 */
final class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = OrganizationContext::organizationId();

        if ($organizationId !== null) {
            $builder->where($model->qualifyColumn('organization_id'), $organizationId);
        }
    }
}
