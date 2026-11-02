<?php

namespace App\Modules\Tenancy;

use App\Models\Organization;

/**
 * Holds the organization resolved for the current request.
 *
 * Bound as a scoped singleton (AppServiceProvider) so the value is reset
 * between requests. The SetOrganizationContext middleware populates it from
 * the session (or falls back to the user's personal organization).
 */
final class OrganizationContext
{
    private ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function current(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->id;
    }

    /**
     * Static accessor for global scopes and other non-DI contexts.
     * Returns null outside a resolved request (CLI, queue workers).
     */
    public static function organization(): ?Organization
    {
        return app()->resolved(self::class) ? app(self::class)->current() : null;
    }

    public static function organizationId(): ?int
    {
        return self::organization()?->id;
    }
}
