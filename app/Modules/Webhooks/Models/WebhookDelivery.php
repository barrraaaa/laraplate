<?php

namespace App\Modules\Webhooks\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One delivery attempt for one event at one endpoint.
 *
 * @property int $id
 * @property int $endpoint_id
 * @property string $event
 * @property array<string, mixed> $payload
 * @property int $attempt_count
 * @property int|null $response_code
 * @property string|null $response_body
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $delivered_at
 * @property-read WebhookEndpoint|null $endpoint
 */
#[Fillable(['endpoint_id', 'event', 'payload', 'attempt_count', 'response_code', 'response_body', 'next_attempt_at', 'delivered_at'])]
class WebhookDelivery extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempt_count' => 'integer',
            'response_code' => 'integer',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WebhookEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }
}
