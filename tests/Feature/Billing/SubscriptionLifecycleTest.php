<?php

namespace Tests\Feature\Billing;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * Plan resolution across the subscription lifecycle (PRD 5.5):
 * active/trialing → plan from Stripe price; canceled + grace → still
 * resolved; canceled past grace → free tier.
 */
class SubscriptionLifecycleTest extends TestCase
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
            ->createTeamOrganization($this->owner, ['name' => 'Subscriptions Co']);

        // Cancel/resume hit the provider API in production — the fake
        // performs the same local transitions Cashier would after success.
        $this->app->bind(BillingGateway::class, function () {
            return new class implements BillingGateway
            {
                public function checkout(Organization $organization, Plan $plan, string $interval, ?Coupon $coupon = null): RedirectResponse
                {
                    return Redirect::to('https://checkout.stripe.test');
                }

                public function billingPortalUrl(Organization $organization, string $returnTo): string
                {
                    return 'https://billing.stripe.test/portal';
                }

                public function cancel(Subscription $subscription): void
                {
                    $subscription->forceFill(['ends_at' => now()->addDays(30)])->save();
                }

                public function resume(Subscription $subscription): void
                {
                    $subscription->forceFill(['ends_at' => null])->save();
                }
            };
        });
    }

    public function test_organization_without_subscription_resolves_to_the_free_plan(): void
    {
        $service = app(BillingService::class);

        $this->assertSame('free', $service->currentPlan($this->organization)->slug);
    }

    public function test_active_subscription_resolves_its_plan_and_interval(): void
    {
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->givePriceIds($pro);
        $this->createSubscription($pro->monthly_price_id, 'active');

        $service = app(BillingService::class);

        $this->assertSame('pro', $service->currentPlan($this->organization)->slug);
        $this->assertSame('monthly', $service->currentInterval($this->organization));
    }

    public function test_cancelled_subscription_in_grace_period_keeps_its_plan(): void
    {
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->givePriceIds($pro);
        $subscription = $this->createSubscription($pro->monthly_price_id, 'active');

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('billing.cancel'))
            ->assertRedirect();

        $service = app(BillingService::class);

        $this->assertTrue($subscription->fresh()->onGracePeriod());
        $this->assertSame('pro', $service->currentPlan($this->organization)->slug);
    }

    public function test_cancelled_subscription_past_grace_falls_back_to_free(): void
    {
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->givePriceIds($pro);
        $subscription = $this->createSubscription($pro->monthly_price_id, 'active');
        $subscription->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);
        $subscription->refresh();

        $service = app(BillingService::class);

        $this->assertFalse($subscription->onGracePeriod());
        $this->assertSame('free', $service->currentPlan($this->organization)->slug);
    }

    public function test_resume_revives_a_cancelled_subscription_within_grace(): void
    {
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->givePriceIds($pro);
        $subscription = $this->createSubscription($pro->monthly_price_id, 'active');

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('billing.cancel'));
        $this->assertTrue($subscription->fresh()->canceled());

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('billing.resume'));
        $this->assertFalse($subscription->fresh()->canceled());
    }

    private function givePriceIds(Plan $plan): void
    {
        $plan->forceFill([
            'monthly_price_id' => 'price_'.$plan->slug.'_monthly',
            'yearly_price_id' => 'price_'.$plan->slug.'_yearly',
        ])->save();
    }

    private function createSubscription(?string $priceId, string $status, ?Carbon $endsAt = null): Subscription
    {
        return Subscription::create([
            'billable_type' => Organization::class,
            'billable_id' => $this->organization->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'stripe_status' => $status,
            'stripe_price' => $priceId,
            'quantity' => 1,
            'ends_at' => $endsAt,
        ]);
    }
}
