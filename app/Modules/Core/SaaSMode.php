<?php

namespace App\Modules\Core;

/**
 * Resolves the application tenancy mode (config/saas.php -> mode) and the
 * UI/product gates that derive from it.
 */
final class SaaSMode
{
    public static function current(): string
    {
        return (string) config('saas.mode', 'single');
    }

    public static function isSingle(): bool
    {
        return self::current() === 'single';
    }

    public static function isTeam(): bool
    {
        return self::current() === 'team';
    }

    public static function isHybrid(): bool
    {
        return self::current() === 'hybrid';
    }

    /**
     * Whether shared (team) organizations are part of the product UX.
     */
    public static function organizationsEnabled(): bool
    {
        return ! self::isSingle() && app(FeatureManager::class)->enabled('organizations');
    }
}
