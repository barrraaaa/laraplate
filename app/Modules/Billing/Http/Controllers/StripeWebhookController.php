<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Models\Organization;
use App\Modules\Audit\Services\Auditing;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Transaction;
use App\Modules\Billing\Notifications\PaymentFailed;
use App\Modules\Billing\Notifications\PaymentSucceeded;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Laravel\Cashier\Subscription;

/**
 * Stripe webhook endpoint (PRD 5.5).
 *
 * Signature verification is enforced by Cashier's middleware; our job on
 * top of Cashier's internal state sync is the idempotent local record:
 * transactions carry a unique stripe_event_id so replayed events are
 * no-ops, and invoices are mirror-upserted for fast customer display.
 */
class StripeWebhookController extends WebhookController
{
    public function __construct(
        private Auditing $auditing,
        private WebhookDispatcher $webhooks,
    ) {}

    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        parent::handleInvoicePaymentSucceeded($payload);

        $this->recordPayment($payload, 'succeeded');

        if ($organization = $this->resolveOrganization($payload)) {
            $this->mirrorInvoice($organization, $payload['data']['object'] ?? []);
            $this->notifyUsers($organization, $payload['data']['object'] ?? []);
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        $this->recordPayment($payload, 'failed');

        if ($organization = $this->resolveOrganization($payload)) {
            $this->notifyUsers($organization, $payload['data']['object'] ?? [], failed: true);
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentActionRequired(array $payload)
    {
        parent::handleInvoicePaymentActionRequired($payload);

        $this->recordPayment($payload, 'action_required');

        return $this->successMethod();
    }

    private function recordPayment(array $payload, string $status): void
    {
        $organization = $this->resolveOrganization($payload);

        if ($organization === null) {
            return;
        }

        $object = $payload['data']['object'] ?? [];

        // Idempotency: the unique constraint on stripe_event_id makes a
        // replayed webhook a no-op — duplicates can never create duplicate
        // payment records (PRD 5.5 acceptance criterion).
        $transaction = Transaction::firstOrCreate(
            ['stripe_event_id' => $payload['id']],
            [
                'organization_id' => $organization->id,
                'event_type' => $payload['type'] ?? 'unknown',
                'amount' => (int) ($object['amount_paid'] ?? 0),
                'currency' => strtolower((string) ($object['currency'] ?? 'usd')),
                'status' => $status,
                'payload' => $payload,
            ],
        );

        if ($transaction->wasRecentlyCreated) {
            $this->auditing->record('billing.payment_'.$status, $organization, $transaction, [
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'event_type' => $transaction->event_type,
            ]);

            $event = $status === 'succeeded' ? 'payment.succeeded' : 'payment.failed';

            $this->webhooks->dispatch($organization, $event, [
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'invoice_id' => $object['id'] ?? null,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ]);
        }
    }

    /**
     * Local invoice mirror for the customer area (PRD 5.5: invoice
     * history with status + downloadable document).
     *
     * @param  array<string, mixed>  $invoice
     */
    private function mirrorInvoice(Organization $organization, array $invoice): void
    {
        Invoice::updateOrCreate(
            ['stripe_invoice_id' => $invoice['id'] ?? ''],
            [
                'organization_id' => $organization->id,
                'number' => $invoice['number'] ?? null,
                'amount_due' => (int) ($invoice['amount_due'] ?? 0),
                'amount_paid' => (int) ($invoice['amount_paid'] ?? 0),
                'currency' => strtolower((string) ($invoice['currency'] ?? 'usd')),
                'status' => (string) ($invoice['status'] ?? 'open'),
                'period_start' => ($invoice['period'] ?? null) ? now()->parse($invoice['period']['start']) : null,
                'period_end' => ($invoice['period'] ?? null) ? now()->parse($invoice['period']['end']) : null,
                'pdf_url' => $invoice['invoice_pdf'] ?? null,
                'hosted_url' => $invoice['hosted_invoice_url'] ?? null,
                'paid_at' => ($invoice['status'] ?? null) === 'paid' ? now() : null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function notifyUsers(Organization $organization, array $invoice, bool $failed = false): void
    {
        $total = (int) ($invoice['amount_paid'] ?? $invoice['amount_due'] ?? 0);

        $organization->memberships()
            ->with('user')
            ->get()
            ->each(fn ($membership) => $failed
                ? $membership->user->notify(new PaymentFailed($organization, $invoice['attempt_count'] ?: null))
                : $membership->user->notify(new PaymentSucceeded($organization, $total, now())));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOrganization(array $payload): ?Organization
    {
        $object = $payload['data']['object'] ?? [];

        $customerId = $object['customer'] ?? null;

        if (is_string($customerId) && $customerId !== '') {
            $organization = Organization::query()
                ->where('stripe_id', $customerId)
                ->first();

            if ($organization !== null) {
                return $organization;
            }
        }

        $subscriptionId = $object['subscription'] ?? null;

        if (is_string($subscriptionId) && $subscriptionId !== '') {
            $subscription = Subscription::query()
                ->where('stripe_id', $subscriptionId)
                ->first();

            // The subscriptions table is polymorphic; only organization
            // billables are understood here.
            if ($subscription !== null && $subscription->getAttribute('billable_type') === Organization::class) {
                return Organization::query()
                    ->whereKey($subscription->getAttribute('billable_id'))
                    ->first();
            }
        }

        return null;
    }
}
