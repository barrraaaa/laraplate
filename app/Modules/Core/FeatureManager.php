<?php

namespace App\Modules\Core;

use App\Modules\Core\Exceptions\FeatureDisabledException;

/**
 * Resolves the feature flags declared in config/saas.php.
 *
 * Modules must call {@see FeatureManager::require()} before executing
 * behaviour that depends on an optional feature, so that a disabled
 * feature fails clearly instead of corrupting data or 404-ing mysteriously.
 */
final class FeatureManager
{
    /**
     * @param  array<string, bool>  $features
     */
    public function __construct(private readonly array $features) {}

    public function enabled(string $feature): bool
    {
        // Config-first: runtime config changes (and config caching) take
        // effect without re-binding the singleton. The constructor array
        // remains the fallback for direct instantiation.
        return (bool) config("saas.features.{$feature}", $this->features[$feature] ?? false);
    }

    public function disabled(string $feature): bool
    {
        return ! $this->enabled($feature);
    }

    /**
     * Assert that a feature is enabled.
     *
     * @throws FeatureDisabledException
     */
    public function require(string $feature): void
    {
        if ($this->disabled($feature)) {
            throw new FeatureDisabledException($feature);
        }
    }

    /**
     * @return array<string, bool>
     */
    public function all(): array
    {
        return $this->features;
    }
}
