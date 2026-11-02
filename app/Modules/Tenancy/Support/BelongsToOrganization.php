<?php

namespace App\Modules\Tenancy\Support;

use App\Models\Organization;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as tenant-owned.
 *
 * Applies two invariants automatically:
 *
 *  1. Reads are scoped to the active organization via OrganizationScope —
 *     removing the global scope requires an explicit, audited override
 *     (e.g. the admin panel).
 *  2. Writes inherit the active organization when organization_id is not
 *     set explicitly. Models created during a request can therefore never
 *     leak into the wrong tenant by omission.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') === null) {
                $model->setAttribute('organization_id', OrganizationContext::organizationId());
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
