<?php

namespace Tests\Feature\Notifications;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD 5.7: in-app notifications — billing events feed the database
 * channel and appear as user notifications.
 */
class InAppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Notif Co']);

        $this->organization->update(['stripe_id' => 'cus_notif_1']);
    }

    public function test_payment_success_webhook_creates_in_app_notifications(): void
    {
        $payload = [
            'id' => 'evt_notif_1',
            'object' => 'event',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_notif_1',
                    'object' => 'invoice',
                    'customer' => 'cus_notif_1',
                    'currency' => 'usd',
                    'status' => 'paid',
                    'amount_due' => 1900,
                    'amount_paid' => 1900,
                    'attempt_count' => 1,
                    'number' => 'INV-77',
                    'invoice_pdf' => 'https://pay.stripe.com/invoice/x',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/x',
                    'period' => ['start' => time() - 86400, 'end' => time()],
                ],
            ],
        ];

        $this->postJson('/stripe/webhook', $payload)->assertOk();

        $notification = $this->owner->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Payment received', $notification->data['title']);
        $this->assertNotNull($this->owner->unreadNotifications()->first());
    }

    public function test_marking_notifications_read_updates_the_bell_state(): void
    {
        $payload = [
            'id' => 'evt_notif_2',
            'object' => 'event',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_notif_2',
                    'object' => 'invoice',
                    'customer' => 'cus_notif_1',
                    'currency' => 'usd',
                    'status' => 'paid',
                    'amount_due' => 1900,
                    'amount_paid' => 1900,
                    'period' => ['start' => time() - 86400, 'end' => time()],
                ],
            ],
        ];

        $this->postJson('/stripe/webhook', $payload)->assertOk();

        $notification = $this->owner->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($this->owner)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
