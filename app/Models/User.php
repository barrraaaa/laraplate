<?php

namespace App\Models;

use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property bool $is_platform_admin
 * @property string|null $avatar_path
 */
#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        // Account deletion (PRD 5.1): the personal workspace dies with its
        // user; team organizations survive via created_by -> null,
        // ownership transfer safeguards land with the full tenancy module.
        static::deleting(fn (User $user): ?bool => $user->personalOrganization()?->delete());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return BelongsToMany<Organization, $this, Pivot, 'pivot'> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The invisible single-user workspace.
     */
    public function personalOrganization(): ?Organization
    {
        return $this->organizations()->where('personal', true)->first();
    }

    public function organizationRole(Organization $organization): ?OrganizationRole
    {
        return $this->memberships()
            ->firstWhere('organization_id', $organization->id)
            ?->role;
    }

    public function hasOrganizationRole(Organization $organization, OrganizationRole ...$roles): bool
    {
        $role = $this->organizationRole($organization);

        return $role !== null && in_array($role, $roles, true);
    }

    public function isOrganizationAdmin(Organization $organization): bool
    {
        return $this->hasOrganizationRole(
            $organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        );
    }

    /**
     * Queued variant — transactional mail never blocks the request.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }
}
