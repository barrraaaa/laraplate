<?php

namespace Tests\Unit;

use App\Modules\Core\Exceptions\FeatureDisabledException;
use App\Modules\Core\FeatureManager;
use Tests\TestCase;

class FeatureManagerTest extends TestCase
{
    public function test_reports_enabled_and_disabled_features(): void
    {
        $manager = new FeatureManager([
            'organizations' => true,
            'webhooks' => false,
        ]);

        $this->assertTrue($manager->enabled('organizations'));
        $this->assertTrue($manager->disabled('webhooks'));
        $this->assertFalse($manager->enabled('webhooks'));
        $this->assertFalse($manager->enabled('unknown_feature'));
    }

    public function test_require_passes_for_enabled_features(): void
    {
        $manager = new FeatureManager(['billing' => true]);

        $manager->require('billing');

        $this->addToAssertionCount(1);
    }

    public function test_require_throws_a_clear_exception_for_disabled_features(): void
    {
        $manager = new FeatureManager(['billing' => false]);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('billing');

        $manager->require('billing');
    }

    public function test_container_resolves_the_configured_features(): void
    {
        $this->assertTrue(app(FeatureManager::class)->enabled('billing'));
        $this->assertFalse(app(FeatureManager::class)->enabled('webhooks'));
    }
}
