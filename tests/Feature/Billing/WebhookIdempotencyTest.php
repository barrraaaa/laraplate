<?php

namespace Tests\Feature\Billing;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Transaction;
use App\Modules\Billing\Notifications\PaymentFailed;
use App\Modules\Billing\Notifications\PaymentSucceeded;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * PRD 5.5 acceptance: duplicate webhook delivery must not create
 * duplicate payments, subscriptions, or entitlement changes.
 */
class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Webhook Co']);

        $this->organization->update(['stripe_id' => 'cus_test_1']);
    }

    public function test_payment_success_event_is_recorded_once(): void
    {
        Notification::fake();

        $payload = $this->invoicePayload('evt_1', 'succeeded');

        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->postJson('/stripe/webhook', $payload)->assertOk(); // replay!

        $this->assertSame(1, Transaction::query()->where('stripe_event_id', 'evt_1')->count());
        $this->assertSame(1, Invoice::query()->where('stripe_invoice_id', 'in_test_1')->count());

        $transaction = Transaction::query()->firstWhere('stripe_event_id', 'evt_1');
        $this->assertSame('succeeded', $transaction->status);
        $this->assertSame(1900, $transaction->amount);
        $this->assertSame($this->organization->id, $transaction->organization_id);

        Notification::assertSentTo($this->owner, PaymentSucceeded::class);
    }

    public function test_payment_failed_event_is_recorded_without_duplicates(): void
    {
        Notification::fake();

        $payload = $this->invoicePayload('evt_fail_9', 'failed');

        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->postJson('/stripe/webhook', $payload)->assertOk();

        $this->assertSame(1, Transaction::query()->where('stripe_event_id', 'evt_fail_9')->count());
        $this->assertSame('failed', Transaction::query()->firstWhere('stripe_event_id', 'evt_fail_9')->status);

        Notification::assertSentTo($this->owner, PaymentFailed::class);
    }

    public function test_unknown_events_are_ignored_gracefully(): void
    {
        $payload = $this->invoicePayload('evt_unknown', null, 'charge.dispute.created');

        $this->postJson('/stripe/webhook', $payload)->assertOk();

        $this->assertSame(0, Transaction::query()->whereIn('stripe_event_id', ['evt_unknown', 'evt_1'])->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(string $eventId, ?string $paymentState, ?string $eventType = null): array
    {
        $paid = $paymentState === 'succeeded';
        $eventType ??= $paid ? 'invoice.payment_succeeded' : 'invoice.payment_failed';

        return [
            'id' => $eventId,
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => 'in_test_1',
                    'object' => 'invoice',
                    'customer' => 'cus_test_1',
                    'currency' => 'usd',
                    'status' => $paid ? 'paid' : 'open',
                    'amount_due' => 1900,
                    'amount_paid' => $paid ? 1900 : 0,
                    'attempt_count' => $paid ? 1 : 2,
                    'number' => 'INV-0001',
                    'invoice_pdf' => 'https://pay.stripe.com/invoice/example',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/example',
                    'period' => ['start' => time() - 86400, 'end' => time()],
                ],
            ],
        ];
    }
}
