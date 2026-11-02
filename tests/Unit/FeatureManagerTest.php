<?php

namespace Tests\Unit;

use App\Modules\Core\Exceptions\FeatureDisabledException;
use App\Modules\Core\FeatureManager;
use Tests\TestCase;

class FeatureManagerTest extends TestCase
{
    public function test_reports_enabled_and_disabled_features_from_runtime_config(): void
    {
        config()->set('saas.features.organizations', true);
        config()->set('saas.features.webhooks', false);

        $manager = new FeatureManager([]);

        $this->assertTrue($manager->enabled('organizations'));
        $this->assertTrue($manager->disabled('webhooks'));
        $this->assertFalse($manager->enabled('webhooks'));
        $this->assertFalse($manager->enabled('unknown_feature'));
    }

    public function test_constructor_array_acts_as_fallback_for_unconfigured_keys(): void
    {
        $manager = new FeatureManager(['custom_module' => true]);

        $this->assertTrue($manager->enabled('custom_module'));
    }

    public function test_require_passes_for_enabled_features(): void
    {
        config()->set('saas.features.billing', true);

        (new FeatureManager([]))->require('billing');

        $this->addToAssertionCount(1);
    }

    public function test_require_throws_a_clear_exception_for_disabled_features(): void
    {
        config()->set('saas.features.billing', false);

        $manager = new FeatureManager([]);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('billing');

        $manager->require('billing');
    }

    public function test_container_resolves_the_configured_features_and_follows_runtime_changes(): void
    {
        config()->set('saas.features.webhooks', false);

        $this->assertFalse(app(FeatureManager::class)->enabled('webhooks'));

        config()->set('saas.features.webhooks', true);

        $this->assertTrue(app(FeatureManager::class)->enabled('webhooks'));
    }
}
