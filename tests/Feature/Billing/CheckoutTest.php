<?php

namespace Tests\Feature\Billing;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_admin_can_start_a_paid_checkout(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $this->app->bind(BillingGateway::class, fn () => new FakeGateway);

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('billing.checkout'), ['plan_id' => $pro->id, 'interval' => 'monthly'])
            ->assertRedirect('https://checkout.stripe.test/pro');
    }

    public function test_checkout_on_the_free_plan_fails_cleanly(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $free = Plan::query()->where('slug', 'free')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('billing.show'))
            ->post(route('billing.checkout'), ['plan_id' => $free->id, 'interval' => 'monthly'])
            ->assertSessionHasErrors('checkout');
    }

    public function test_member_without_admin_role_cannot_checkout(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $member = User::factory()->create();
        app(MembershipService::class)
            ->join($organization, $member, OrganizationRole::Member);

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        // Checkout operates on the ACTIVE organization — put the member
        // inside the team context; there they are not an admin.
        $this->actingAs($member)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('billing.checkout'), ['plan_id' => $pro->id, 'interval' => 'monthly'])
            ->assertForbidden();
    }

    public function test_invalid_interval_is_rejected(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('billing.show'))
            ->post(route('billing.checkout'), ['plan_id' => $pro->id, 'interval' => 'weekly'])
            ->assertSessionHasErrors('interval');
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithOwner(): array
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationService::class)
            ->createTeamOrganization($owner, ['name' => 'Billing Co '.fake()->uuid()]);

        return [$organization, $owner];
    }
}

final class FakeGateway implements BillingGateway
{
    public function checkout(Organization $organization, Plan $plan, string $interval, ?Coupon $coupon = null): RedirectResponse
    {
        return Redirect::to('https://checkout.stripe.test/'.$plan->slug);
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
