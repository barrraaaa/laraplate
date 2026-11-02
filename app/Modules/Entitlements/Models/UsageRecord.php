<?php

namespace App\Modules\Entitlements\Models;

use App\Models\Organization;
use Database\Factories\Entitlements\UsageRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metered consumption against a quota (PRD 5.6). Aggregated per
 * (organization, metric, period) — the period window is configurable via
 * config/saas.php -> usage.period.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $metric
 * @property int $quantity
 */
#[Fillable(['organization_id', 'metric', 'quantity', 'period_start', 'period_end'])]
class UsageRecord extends Model
{
    /** @use HasFactory<UsageRecordFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
