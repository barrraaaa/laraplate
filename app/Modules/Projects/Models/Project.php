<?php

namespace App\Modules\Projects\Models;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Tenancy\Support\BelongsToOrganization;
use Database\Factories\Projects\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Example domain module (PRD 5.12).
 *
 * Everything a buyer's module needs is demonstrated here:
 *  - BelongsToOrganization: tenant scoping + automatic attribution
 *  - validation via FormRequests
 *  - authorization via a module policy + organization roles
 *  - entitlement enforcement (projects.max quota) at the service border
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $created_by
 * @property string $name
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Carbon|null $created_at
 * @property-read Organization|null $organization
 * @property-read User|null $creator
 */
#[Fillable(['organization_id', 'created_by', 'name', 'description', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
