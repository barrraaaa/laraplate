<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Commercial plan definition (PRD 5.5). A "free" plan has no Stripe price
 * ids; paid plans reference one price per billing interval. The features
 * JSON carries the entitlement catalog: feature flags + quotas.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $monthly_price_id
 * @property string|null $yearly_price_id
 * @property array{features?: array<string, bool>, quotas?: array<string, int>}|null $features
 * @property bool $active
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'description', 'monthly_price_id', 'yearly_price_id', 'features', 'active', 'sort_order'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isFree(): bool
    {
        return $this->monthly_price_id === null && $this->yearly_price_id === null;
    }

    public function priceId(string $interval): ?string
    {
        return $interval === 'yearly' ? $this->yearly_price_id : $this->monthly_price_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function entitlements(): array
    {
        return $this->features ?? [];
    }

    public function featureFlag(string $feature): bool
    {
        return (bool) data_get($this->features, "features.{$feature}", false);
    }

    public function quota(string $metric): ?int
    {
        // Direct access: quota keys themselves contain dots
        // (e.g. "projects.max"), which data_get() would split apart.
        $quota = $this->features['quotas'][$metric] ?? null;

        return $quota === null ? null : (int) $quota;
    }
}
