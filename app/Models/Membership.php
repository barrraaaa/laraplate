<?php

namespace App\Models;

use App\Modules\Tenancy\Enums\OrganizationRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property OrganizationRole $role
 * @property-read User|null $user
 * @property-read Organization|null $organization
 *
 * Links a user to an organization with a curated role (the membership is
 * the authorization source of truth — see SetOrganizationContext and the
 * organization policies).
 */
#[Fillable(['organization_id', 'user_id', 'role'])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === OrganizationRole::Owner;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [OrganizationRole::Owner, OrganizationRole::Admin], true);
    }
}
