<?php

namespace Tests\Feature\Webhooks;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use App\Modules\Tenancy\Services\MembershipService;
use App\Modules\Tenancy\Services\OrganizationService;
use App\Modules\Webhooks\Models\WebhookDelivery;
use App\Modules\Webhooks\Models\WebhookEndpoint;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PRD 5.8 P1: outbound webhooks — endpoint management, event fan-out,
 * HMAC signing, retries and the delivery log.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('saas.mode', 'team');

        $this->owner = User::factory()->create();
        $this->organization = app(OrganizationService::class)
            ->createTeamOrganization($this->owner, ['name' => 'Webhook Co']);
    }

    public function test_admins_can_register_an_endpoint(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('webhooks.store'), [
                'url' => 'https://hooks.example.com/events',
                'events' => ['organization.member_joined', 'payment.succeeded'],
            ])
            ->assertRedirect();

        $endpoint = WebhookEndpoint::query()->firstOrFail();

        $this->assertSame($this->organization->id, $endpoint->organization_id);
        $this->assertSame(
            ['organization.member_joined', 'payment.succeeded'],
            $endpoint->events,
        );
    }

    public function test_non_admins_cannot_manage_endpoints(): void
    {
        $member = User::factory()->create();
        app(MembershipService::class)->join($this->organization, $member, OrganizationRole::Member);

        $this->actingAs($member)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->post(route('webhooks.store'), [
                'url' => 'https://hooks.example.com/events',
                'events' => ['payment.succeeded'],
            ])
            ->assertForbidden();
    }

    public function test_events_fan_out_to_subscribed_endpoints_with_hmac_signature(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('ok', 200)]);

        WebhookEndpoint::query()->create([
            'organization_id' => $this->organization->id,
            'url' => 'https://hooks.example.com/events',
            'secret' => 'topsecret',
            'events' => ['organization.member_joined'],
            'active' => true,
            'created_by' => $this->owner->id,
        ]);

        $member = User::factory()->create();

        app(MembershipService::class)->join($this->organization, $member, OrganizationRole::Member);

        $delivery = WebhookDelivery::query()->firstOrFail();

        $this->assertSame('organization.member_joined', $delivery->event);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertSame(200, $delivery->response_code);

        Http::assertSent(function ($request) use ($delivery): bool {
            $endpoint = $delivery->endpoint;
            $signature = $request->header(WebhookDispatcher::HEADER_SIGNATURE)[0] ?? '';
            $dispatcher = app(WebhookDispatcher::class);

            return $dispatcher->verify(
                $endpoint,
                (string) $request->body(),
                $signature,
            );
        });
    }

    public function test_inactive_or_unsubscribed_endpoints_are_skipped(): void
    {
        Http::fake();

        WebhookEndpoint::query()->create([
            'organization_id' => $this->organization->id,
            'url' => 'https://hooks.example.com/inactive',
            'secret' => 'secret',
            'events' => ['organization.member_joined'],
            'active' => false,
            'created_by' => $this->owner->id,
        ]);

        WebhookEndpoint::query()->create([
            'organization_id' => $this->organization->id,
            'url' => 'https://hooks.example.com/other',
            'secret' => 'secret',
            'events' => ['payment.succeeded'],
            'active' => true,
            'created_by' => $this->owner->id,
        ]);

        app(MembershipService::class)->join(
            $this->organization,
            User::factory()->create(),
            OrganizationRole::Member,
        );

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::query()->count());
    }

    public function test_failing_deliveries_retry_up_to_the_attempt_budget(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('boom', 500)]);

        WebhookEndpoint::query()->create([
            'organization_id' => $this->organization->id,
            'url' => 'https://hooks.example.com/failing',
            'secret' => 'secret',
            'events' => ['organization.member_joined'],
            'active' => true,
            'created_by' => $this->owner->id,
        ]);

        app(MembershipService::class)->join(
            $this->organization,
            User::factory()->create(),
            OrganizationRole::Member,
        );

        $delivery = WebhookDelivery::query()->firstOrFail();

        $this->assertNull($delivery->delivered_at);
        $this->assertGreaterThanOrEqual(1, $delivery->attempt_count);
    }

    public function test_signatures_are_tamper_evident(): void
    {
        $endpoint = WebhookEndpoint::query()->create([
            'organization_id' => $this->organization->id,
            'url' => 'https://hooks.example.com/events',
            'secret' => 'supersecret',
            'events' => ['payment.succeeded'],
            'active' => true,
            'created_by' => $this->owner->id,
        ]);

        $dispatcher = app(WebhookDispatcher::class);
        $payload = '{"hello":"world"}';

        $this->assertTrue($dispatcher->verify($endpoint, $payload, $dispatcher->sign($endpoint, $payload)));
        $this->assertFalse($dispatcher->verify($endpoint, $payload.'x', $dispatcher->sign($endpoint, $payload)));
    }
}
