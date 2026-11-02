<?php

namespace App\Models;

use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Webhooks\Services\WebhookDispatcher;
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
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property array<string, bool>|null $notification_preferences
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
        static::deleting(function (User $user): ?bool {
            $organizations = $user->organizations()->get();
            $dispatcher = app(WebhookDispatcher::class);

            foreach ($organizations as $organization) {
                $dispatcher->dispatch($organization, 'user.deleted', [
                    'id' => $user->id,
                    'email' => $user->email,
                    'deleted_at' => now()->toIso8601String(),
                ]);
            }

            return $user->personalOrganization()?->delete();
        });
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
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Non-essential notification channels (PRD 5.7 P1). Defaults to
     * enabled when no preference is stored.
     */
    public function wantsEmail(string $key): bool
    {
        $preferences = $this->notification_preferences ?? [];

        return (bool) ($preferences[$key] ?? true);
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
