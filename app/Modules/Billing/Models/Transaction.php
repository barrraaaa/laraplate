<?php

namespace App\Modules\Billing\Models;

use App\Models\Organization;
use Database\Factories\Billing\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Financial event log (PRD 5.5/5.11): one row per Stripe webhook event.
 * The unique stripe_event_id makes webhook replay safe — duplicate
 * delivery can never create duplicate transactions.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property Carbon|null $created_at
 * @property-read Organization|null $organization
 */
#[Fillable(['organization_id', 'stripe_event_id', 'event_type', 'amount', 'currency', 'status', 'payload'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Amount in major units (e.g. 10.50 for USD).
     */
    public function formattedAmount(): string
    {
        return number_format($this->amount / 100, 2);
    }
}
