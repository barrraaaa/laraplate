<?php

namespace App\Modules\Billing\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Transaction;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Core\FeatureManager;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

/**
 * Plan/subscription domain logic (PRD 5.5). Resolution rules:
 *   - the active subscription's Stripe price maps back to a Plan row;
 *   - no active subscription (or canceled past grace) resolves to the
 *     free plan;
 *   - with the billing feature disabled, entitlements are unlimited.
 */
final class BillingService
{
    public function __construct(
        private BillingGateway $gateway,
        private FeatureManager $features,
    ) {}

    public function enabled(): bool
    {
        return $this->features->enabled('billing');
    }

    /**
     * @return Collection<int, Plan>
     */
    public function plans(): Collection
    {
        return Plan::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function subscription(Organization $organization): ?Subscription
    {
        return $organization->subscription('default');
    }

    public function currentPlan(Organization $organization): Plan
    {
        $subscription = $this->subscription($organization);

        // A canceled subscription still grants access during its grace
        // period; once that lapses the organization falls back to free.
        if ($subscription !== null && ($subscription->active() || $subscription->onGracePeriod())) {
            $priceId = $this->resolvedPriceId($subscription);

            if ($priceId !== null) {
                $plan = $this->findByPriceId($priceId);

                if ($plan !== null) {
                    return $plan;
                }
            }
        }

        return $this->freePlan();
    }

    public function currentInterval(Organization $organization): ?string
    {
        $subscription = $this->subscription($organization);
        $plan = $this->currentPlan($organization);

        if ($subscription === null) {
            return $plan->isFree() ? null : 'monthly';
        }

        if ($this->resolvedPriceId($subscription) === $plan->yearly_price_id) {
            return 'yearly';
        }

        return 'monthly';
    }

    /**
     * Price resolution prefers subscription items (set from webhooks and
     * multi-price subscriptions) and falls back to the subscription's
     * own legacy price column.
     */
    private function resolvedPriceId(Subscription $subscription): ?string
    {
        $priceId = $subscription->items->first()?->getAttribute('stripe_price')
            ?? $subscription->getAttribute('stripe_price');

        return is_string($priceId) && $priceId !== '' ? $priceId : null;
    }

    public function freePlan(): Plan
    {
        return Plan::query()
            ->where('slug', (string) config('saas.plans.free_slug', 'free'))
            ->firstOrFail();
    }

    public function findByPriceId(string $priceId): ?Plan
    {
        return Plan::query()
            ->where('monthly_price_id', $priceId)
            ->orWhere('yearly_price_id', $priceId)
            ->first();
    }

    public function checkout(Organization $organization, Plan $plan, string $interval)
    {
        return $this->gateway->checkout($organization, $plan, $interval);
    }

    public function billingPortalUrl(Organization $organization, string $returnTo): string
    {
        return $this->gateway->billingPortalUrl($organization, $returnTo);
    }

    public function cancel(Organization $organization): void
    {
        $subscription = $this->subscription($organization);

        if ($subscription !== null && ! $subscription->canceled()) {
            $this->gateway->cancel($subscription);
        }
    }

    public function resume(Organization $organization): void
    {
        $subscription = $this->subscription($organization);

        if ($subscription !== null && $subscription->onGracePeriod()) {
            $this->gateway->resume($subscription);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Invoice>
     */
    public function invoices(Organization $organization, int $limit = 10)
    {
        return Invoice::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Transaction>
     */
    public function transactions(Organization $organization, int $limit = 10)
    {
        return Transaction::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
