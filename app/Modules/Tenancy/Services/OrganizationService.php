<?php

namespace App\Modules\Tenancy\Services;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Services\Auditing;
use App\Modules\Tenancy\Enums\OrganizationRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Organization lifecycle operations.
 */
final class OrganizationService
{
    public function __construct(
        private MembershipService $memberships,
        private Auditing $auditing,
    ) {}

    /**
     * The invisible workspace behind "single-user mode": one per user,
     * owned by the user, never surfaced by the UI in single mode.
     */
    public function createPersonalOrganization(User $user): Organization
    {
        return DB::transaction(function () use ($user): Organization {
            $organization = Organization::create([
                'name' => $user->name,
                'slug' => $this->uniquePersonalSlug($user),
                'personal' => true,
                'created_by' => $user->id,
            ]);

            $this->memberships->join($organization, $user, OrganizationRole::Owner);

            return $organization;
        });
    }

    /**
     * Find-or-create the user's personal organization (invariant repair;
     * safe to call repeatedly).
     */
    public function personalOrganizationFor(User $user): Organization
    {
        return $user->personalOrganization() ?? $this->createPersonalOrganization($user);
    }

    /**
     * Team-mode workspace: the creating user becomes its owner.
     *
     * @param  array{name: string, slug?: string|null}  $data
     */
    public function createTeamOrganization(User $owner, array $data): Organization
    {
        return DB::transaction(function () use ($owner, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['name']),
                'personal' => false,
                'created_by' => $owner->id,
            ]);

            $this->memberships->join($organization, $owner, OrganizationRole::Owner);

            $this->auditing->record('organization.created', $organization, $organization);

            return $organization;
        });
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update([
            'name' => $data['name'] ?? $organization->name,
            'slug' => array_key_exists('slug', $data)
                ? $this->uniqueSlug($data['slug'] ?? $data['name'] ?? $organization->name)
                : $organization->slug,
        ]);

        return $organization;
    }

    /**
     * Archive a shared workspace. Personal organizations are structural
     * (the single-user tenancy anchor) and cannot be archived.
     */
    public function archive(Organization $organization): void
    {
        throw_if(
            $organization->isPersonal(),
            InvalidArgumentException::class,
            'A personal organization cannot be archived.',
        );

        $organization->delete();

        $this->auditing->record('organization.archived', $organization, $organization);
    }

    private function uniqueSlug(string $name, ?string $existingId = null): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $counter = 2;

        while (Organization::query()->where('slug', $slug)->whereNot('id', $existingId)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function uniquePersonalSlug(User $user): string
    {
        return Str::slug($user->name).'-'.$user->id;
    }
}
