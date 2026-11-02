<?php

namespace Tests\Feature\Entitlements;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Entitlements\Notifications\UsageAlert;
use App\Modules\Entitlements\Services\EntitlementService;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * PRD 5.6 P1: one usage alert per (metric, period) once the configured
 * threshold is crossed, delivered to the organization's admins.
 */
class UsageAlertTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Alerts Co']);
    }

    public function test_alert_fires_once_when_usage_crosses_the_threshold(): void
    {
        Notification::fake();

        $entitlements = app(EntitlementService::class);

        // Free plan storage.mb limit = 100 → 80% at 80 units.
        $entitlements->record($this->organization, 'storage.mb', 80);

        Notification::assertSentTo($this->owner, UsageAlert::class);

        Notification::fake();
        $entitlements->record($this->organization, 'storage.mb', 5);

        Notification::assertNotSentTo($this->owner, UsageAlert::class);
    }

    public function test_usage_below_the_threshold_does_not_alert(): void
    {
        Notification::fake();

        app(EntitlementService::class)->record($this->organization, 'storage.mb', 50);

        Notification::assertNotSentTo($this->owner, UsageAlert::class);
    }

    public function test_unlimited_metrics_never_alert(): void
    {
        Notification::fake();

        config()->set('saas.features.billing', false);

        app(EntitlementService::class)->record($this->organization, 'storage.mb', 5000);

        Notification::assertNotSentTo($this->owner, UsageAlert::class);
    }
}
