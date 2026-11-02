<?php

namespace Tests\Feature\Billing;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * PRD 5.5 P1: coupons — admin management, checkout validation, caps and
 * per-organization redemption rules.
 */
class CouponsTest extends TestCase
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
            ->createTeamOrganization($this->owner, ['name' => 'Coupon Co']);

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();
        $pro->forceFill(['monthly_price_id' => 'price_pro_monthly', 'yearly_price_id' => 'price_pro_yearly'])->save();
    }

    public function test_platform_admin_can_create_a_coupon(): void
    {
        $this->owner->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($this->owner)
            ->post('/admin/coupons', [
                'code' => 'LAUNCHPARTY',
                'percent_off' => 20,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('coupons', [
            'code' => 'LAUNCHPARTY',
            'percent_off' => 20,
            'active' => true,
        ]);
    }

    public function test_coupon_requires_exactly_one_discount_rule(): void
    {
        $this->owner->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($this->owner)
            ->post('/admin/coupons', ['code' => 'BOTH'])
            ->assertSessionHasErrors('percent_off');
    }

    public function test_checkout_applies_a_valid_coupon(): void
    {
        $this->app->bind(BillingGateway::class, fn () => new CouponCapturingGateway);

        $coupon = Coupon::factory()->create(['code' => 'SAVE20', 'percent_off' => 20]);
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('billing.checkout'), [
                'plan_id' => $pro->id,
                'interval' => 'monthly',
                'coupon_code' => 'SAVE20',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_expired_or_inactive_coupons_are_rejected(): void
    {
        Coupon::factory()->inactive()->create(['code' => 'DEAD']);
        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->from(route('billing.show'))
            ->post(route('billing.checkout'), [
                'plan_id' => $pro->id,
                'interval' => 'monthly',
                'coupon_code' => 'DEAD',
            ])
            ->assertSessionHasErrors('coupon');
    }

    public function test_coupon_can_only_be_redeemed_once_per_organization(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'ONCE', 'percent_off' => 30]);
        $coupon->redeemedBy()->attach($this->organization);

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->from(route('billing.show'))
            ->post(route('billing.checkout'), [
                'plan_id' => $pro->id,
                'interval' => 'monthly',
                'coupon_code' => 'ONCE',
            ])
            ->assertSessionHasErrors('coupon');
    }
}

final class CouponCapturingGateway implements BillingGateway
{
    public function checkout(Organization $organization, Plan $plan, string $interval, ?Coupon $coupon = null): RedirectResponse
    {
        return Redirect::to('https://checkout.stripe.test/'.$plan->slug.($coupon ? '/coupon' : ''));
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
}
