<?php

namespace App\Modules\Webhooks\Jobs;

use App\Modules\Webhooks\Models\WebhookDelivery;
use App\Modules\Webhooks\Models\WebhookEndpoint;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Performs one delivery attempt. Retries with exponential backoff up to
 * MAX_ATTEMPTS; failures beyond the budget stay failed so the delivery
 * log tells the full story.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public const MAX_ATTEMPTS = 3;

    public int $tries = self::MAX_ATTEMPTS;

    public int $backoff = 60;

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(WebhookDispatcher $dispatcher): void
    {
        $endpoint = WebhookEndpoint::query()->find($this->delivery->endpoint_id);

        if ($endpoint === null || ! $endpoint->active) {
            return;
        }

        $payloadJson = json_encode($this->delivery->payload, JSON_THROW_ON_ERROR);

        $this->delivery->increment('attempt_count');

        [$code, $body] = $this->request($dispatcher, $endpoint, $payloadJson);

        if ($code !== null && $code >= 200 && $code < 300) {
            $this->delivery->forceFill([
                'response_code' => $code,
                'response_body' => $body,
                'delivered_at' => now(),
                'next_attempt_at' => null,
            ])->save();

            return;
        }

        $this->delivery->forceFill([
            'response_code' => $code,
            'response_body' => $body,
        ]);

        if ((int) $this->delivery->attempt_count < self::MAX_ATTEMPTS) {
            $this->delivery->forceFill([
                'next_attempt_at' => now()->addMinutes((int) $this->delivery->attempt_count * 10),
            ])->save();

            $this->release((int) $this->delivery->attempt_count * 60);

            return;
        }

        $this->delivery->forceFill(['next_attempt_at' => null])->save();
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function request(WebhookDispatcher $dispatcher, WebhookEndpoint $endpoint, string $payloadJson): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    WebhookDispatcher::HEADER_SIGNATURE => $dispatcher->sign($endpoint, $payloadJson),
                    WebhookDispatcher::HEADER_EVENT => $this->delivery->event,
                    WebhookDispatcher::HEADER_DELIVERY => (string) $this->delivery->id,
                ])
                ->post($endpoint->url, $this->delivery->payload);

            return [$response->status(), substr($response->body(), 0, 2000)];
        } catch (Throwable $e) {
            return [null, substr($e->getMessage(), 0, 2000)];
        }
    }
}
