<?php

namespace App\Modules\Billing\Providers;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Laravel\Cashier\Subscription;

/**
 * Stripe implementation via Laravel Cashier. Subscriptions, customers and
 * checkout sessions are delegated to Cashier; the local domain (plans,
 * transactions, invoices) stays ours.
 */
final class StripeGateway implements BillingGateway
{
    public function checkout(Organization $organization, Plan $plan, string $interval): RedirectResponse
    {
        $priceId = $plan->priceId($interval);

        throw_unless(
            $priceId !== null,
            InvalidArgumentException::class,
            "Plan [{$plan->slug}] has no Stripe price for the {$interval} interval.",
        );

        if (! $organization->hasStripeId()) {
            $organization->createAsStripeCustomer([
                'name' => $organization->name,
                'metadata' => [
                    'organization_id' => $organization->id,
                    'organization_slug' => $organization->slug ?? 'personal',
                ],
            ]);
        }

        return $organization
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.show').'?checkout=success',
                'cancel_url' => route('billing.show').'?checkout=cancelled',
            ])->redirect();
    }

    public function billingPortalUrl(Organization $organization, string $returnTo): string
    {
        if (! $organization->hasStripeId()) {
            $organization->createAsStripeCustomer([
                'name' => $organization->name,
            ]);
        }

        return $organization->billingPortalUrl($returnTo);
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->cancel();
    }

    public function resume(Subscription $subscription): void
    {
        $subscription->resume();
    }
}
