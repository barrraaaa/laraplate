<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use App\Modules\Billing\Http\Requests\CheckoutRequest;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Entitlements\Services\EntitlementService;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Customer billing area (PRD 5.5): plans, checkout, cancel/resume, portal
 * and the local invoice/transaction history. Always operates on the
 * active organization context.
 */
class BillingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private BillingService $billing,
        private EntitlementService $entitlements,
    ) {}

    public function show(Request $request): Response
    {
        $organization = $this->activeOrganization();
        $subscription = $this->billing->subscription($organization);
        $currentPlan = $this->billing->currentPlan($organization);

        $usage = collect($currentPlan->entitlements()['quotas'] ?? [])
            ->map(fn (?int $limit, string $metric) => [
                'metric' => $metric,
                'limit' => $limit,
                'used' => $this->entitlements->usage($organization, $metric),
            ])
            ->values();

        return Inertia::render('app/Billing/Index', [
            'plans' => $this->billing->plans()->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'features' => $plan->entitlements(),
                'free' => $plan->isFree(),
            ])->values(),
            'currentPlan' => [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'slug' => $currentPlan->slug,
                'features' => $currentPlan->entitlements(),
            ],
            'interval' => $this->billing->currentInterval($organization),
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'onGracePeriod' => $subscription->onGracePeriod(),
                'endsAt' => $subscription->ends_at?->toIso8601String(),
                'trialEndsAt' => $subscription->trial_ends_at?->toIso8601String(),
                'onTrial' => $subscription->onTrial(),
            ] : null,
            'usage' => $usage,
            'invoices' => $this->billing->invoices($organization)->map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number ?? $invoice->stripe_invoice_id,
                'total' => $invoice->formattedTotal(),
                'currency' => strtoupper($invoice->currency),
                'status' => $invoice->status,
                'periodEnd' => $invoice->period_end?->toIso8601String(),
                'pdfUrl' => $invoice->pdf_url,
                'hostedUrl' => $invoice->hosted_url,
            ]),
            'transactions' => $this->billing->transactions($organization)->map(fn ($transaction) => [
                'id' => $transaction->id,
                'eventType' => $transaction->event_type,
                'amount' => $transaction->formattedAmount(),
                'currency' => strtoupper($transaction->currency),
                'status' => $transaction->status,
                'createdAt' => $transaction->created_at->toIso8601String(),
            ]),
            'stripeConfigured' => $this->stripeConfigured(),
            'canManage' => $request->user()->can('manageBilling', $organization),
            'checkoutStatus' => $request->query('checkout'),
        ]);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $organization = $this->activeOrganization();

        $this->authorize('manageBilling', $organization);

        $plan = Plan::query()->findOrFail($request->integer('plan_id'));
        $interval = $request->string('interval')->toString();

        try {
            return $this->billing->checkout($organization, $plan, $interval);
        } catch (Throwable $e) {
            // Provider-side failures (missing prices, network, 3DS blocks)
            // surface as user feedback, never a 500.
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $organization = $this->activeOrganization();

        $this->authorize('manageBilling', $organization);

        $this->billing->cancel($organization);

        return back()->with('status', 'subscription-cancelled');
    }

    public function resume(Request $request): RedirectResponse
    {
        $organization = $this->activeOrganization();

        $this->authorize('manageBilling', $organization);

        $this->billing->resume($organization);

        return back()->with('status', 'subscription-resumed');
    }

    public function portal(Request $request): RedirectResponse
    {
        $organization = $this->activeOrganization();

        $this->authorize('manageBilling', $organization);

        return redirect(
            $this->billing->billingPortalUrl($organization, route('billing.show'))
        );
    }

    private function activeOrganization(): Organization
    {
        return OrganizationContext::organization()
            ?? throw new \RuntimeException('Billing requires an authenticated organization context.');
    }

    private function stripeConfigured(): bool
    {
        return filled(config('saas.billing.stripe.key')) && filled(config('saas.billing.stripe.secret'));
    }
}
