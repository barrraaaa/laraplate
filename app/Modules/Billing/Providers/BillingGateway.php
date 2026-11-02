<?php

namespace App\Modules\Billing\Providers;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Laravel\Cashier\Subscription;

/**
 * Provider-adapter boundary for subscription billing (PRD 5.5). A second
 * provider ships as another implementation — domain code never talks to
 * a provider directly.
 *
 * Implementations return a redirect to the provider's hosted checkout;
 * tests swap in a fake that redirects anywhere.
 */
interface BillingGateway
{
    public function checkout(Organization $organization, Plan $plan, string $interval): RedirectResponse;

    public function billingPortalUrl(Organization $organization, string $returnTo): string;

    public function cancel(Subscription $subscription): void;

    public function resume(Subscription $subscription): void;
}
