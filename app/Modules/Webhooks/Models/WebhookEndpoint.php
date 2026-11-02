<?php

namespace App\Modules\Webhooks\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An organization's outbound webhook endpoint (PRD 5.8 P1): subscribed to
 * a fixed event catalog, signed per-endpoint with an encrypted secret.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $url
 * @property string $secret
 * @property array<int, string> $events
 * @property bool $active
 * @property int|null $created_by
 * @property-read Collection<int, WebhookDelivery> $deliveries
 */
#[Fillable(['organization_id', 'url', 'secret', 'events', 'active', 'created_by'])]
class WebhookEndpoint extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events, true);
    }
}
