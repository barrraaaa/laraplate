<?php

namespace Tests\Feature\Entitlements;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Entitlements\Models\UsageRecord;
use App\Modules\Entitlements\Services\EntitlementService;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * PRD 5.6: feature entitlements, quota definitions, centralized checks,
 * usage recording and limit enforcement.
 */
class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        $owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Entitlements Co']);
    }

    public function test_free_plan_restricts_features_and_quotas(): void
    {
        $entitlements = app(EntitlementService::class);

        $this->assertTrue($entitlements->can($this->organization, 'projects'));
        $this->assertFalse($entitlements->can($this->organization, 'attachments'));
        $this->assertSame(3, $entitlements->quota($this->organization, 'projects.max'));
    }

    public function test_usage_records_and_quota_enforcement(): void
    {
        $entitlements = app(EntitlementService::class);

        $entitlements->record($this->organization, 'projects.max', 2);

        $this->assertSame(2, $entitlements->usage($this->organization, 'projects.max'));
        $this->assertSame(1, $entitlements->remaining($this->organization, 'projects.max'));
        $this->assertFalse($entitlements->overLimit($this->organization, 'projects.max'));

        $entitlements->record($this->organization, 'projects.max');

        $this->assertSame(3, $entitlements->usage($this->organization, 'projects.max'));
        $this->assertTrue($entitlements->overLimit($this->organization, 'projects.max', 1));
    }

    public function test_unlimited_quota_never_enforces(): void
    {
        $business = Plan::query()->where('slug', 'business')->firstOrFail();
        $this->givePriceIds($business);
        $this->subscribeTo($business);

        $entitlements = app(EntitlementService::class);

        // Unlimited projects on Business (quotas.projects.max = null).
        $entitlements->record($this->organization, 'storage.mb', 999999);

        $this->assertNull($entitlements->quota($this->organization, 'projects.max'));
        $this->assertFalse($entitlements->overLimit($this->organization, 'projects.max', 9999999));
    }

    public function test_upgraded_subscription_grants_paid_features(): void
    {
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->givePriceIds($pro);
        $this->subscribeTo($pro);

        $entitlements = app(EntitlementService::class);

        $this->assertTrue($entitlements->can($this->organization, 'attachments'));
        $this->assertSame(25, $entitlements->quota($this->organization, 'projects.max'));
    }

    public function test_usage_from_previous_periods_does_not_count(): void
    {
        UsageRecord::create([
            'organization_id' => $this->organization->id,
            'metric' => 'projects.max',
            'quantity' => 999,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        $entitlements = app(EntitlementService::class);

        $this->assertSame(0, $entitlements->usage($this->organization, 'projects.max'));
    }

    public function test_disabling_billing_feature_disables_enforcement(): void
    {
        config()->set('saas.features.billing', false);

        $entitlements = app(EntitlementService::class);

        $this->assertTrue($entitlements->can($this->organization, 'attachments'));
        $this->assertFalse($entitlements->overLimit($this->organization, 'projects.max', 9999999));
    }

    private function subscribeTo(Plan $plan): Subscription
    {
        return Subscription::create([
            'billable_type' => Organization::class,
            'billable_id' => $this->organization->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'stripe_status' => 'active',
            'stripe_price' => $plan->monthly_price_id,
            'quantity' => 1,
        ]);
    }

    private function givePriceIds(Plan $plan): void
    {
        $plan->forceFill([
            'monthly_price_id' => 'price_'.$plan->slug.'_monthly',
            'yearly_price_id' => 'price_'.$plan->slug.'_yearly',
        ])->save();
    }
}
