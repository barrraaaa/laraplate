<?php

namespace App\Models;

use App\Modules\Tenancy\Enums\OrganizationRole;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The tenant boundary: a workspace (team mode) or the invisible personal
 * organization behind single-user mode.
 */
#[Fillable(['name', 'slug', 'personal', 'settings', 'created_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /** @return HasMany<Membership, $this> */
    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return BelongsToMany<User, $this, Pivot, 'pivot'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isPersonal(): bool
    {
        return (bool) $this->personal;
    }

    /**
     * The current owner membership (role = owner). Ownership transfer
     * rewrites the membership role, not this relation.
     *
     * @return HasOne<Membership, $this>
     */
    public function ownerMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->where('role', OrganizationRole::Owner->value);
    }

    public function owner(): ?User
    {
        return $this->memberships()
            ->firstWhere('role', OrganizationRole::Owner->value)
            ?->user;
    }
}
