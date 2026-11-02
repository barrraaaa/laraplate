<?php

namespace App\Modules\Webhooks\Services;

use App\Models\Organization;
use App\Modules\Core\FeatureManager;
use App\Modules\Webhooks\Jobs\DeliverWebhookJob;
use App\Modules\Webhooks\Models\WebhookDelivery;
use App\Modules\Webhooks\Models\WebhookEndpoint;
use Illuminate\Support\Str;

/**
 * Outbound webhook dispatch (PRD 5.8 P1): fan-out of module events to
 * subscribed, active endpoints. Signing is HMAC-SHA256 over the raw JSON
 * body using the endpoint secret; verification is exposed for buyers who
 * want to verify deliveries on their side.
 */
final class WebhookDispatcher
{
    public const HEADER_SIGNATURE = 'X-Laraplate-Signature';

    public const HEADER_EVENT = 'X-Laraplate-Event';

    public const HEADER_DELIVERY = 'X-Laraplate-Delivery';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Organization $organization, string $event, array $payload): void
    {
        if (! app(FeatureManager::class)->enabled('webhooks')) {
            return;
        }

        $endpoints = WebhookEndpoint::query()
            ->where('organization_id', $organization->id)
            ->where('active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if (! $endpoint->subscribesTo($event)) {
                continue;
            }

            $delivery = WebhookDelivery::query()->create([
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'payload' => $payload,
                'attempt_count' => 0,
            ]);

            DeliverWebhookJob::dispatch($delivery);
        }
    }

    public function sign(WebhookEndpoint $endpoint, string $payloadJson): string
    {
        return hash_hmac('sha256', $payloadJson, $endpoint->secret);
    }

    public function verify(WebhookEndpoint $endpoint, string $payloadJson, string $signature): bool
    {
        return hash_equals($this->sign($endpoint, $payloadJson), $signature);
    }

    public static function eventCatalog(): array
    {
        return (array) config('saas.webhooks.events', []);
    }

    public function generateSecret(): string
    {
        return Str::random(40);
    }
}
