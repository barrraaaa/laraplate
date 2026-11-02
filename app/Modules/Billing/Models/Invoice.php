<?php

namespace App\Modules\Billing\Models;

use App\Models\Organization;
use Database\Factories\Billing\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Local invoice mirror (PRD 5.5): invoice references, totals and document
 * links, populated idempotently from Stripe webhooks so the customer area
 * never needs a live API round-trip per page.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $stripe_invoice_id
 * @property string|null $number
 * @property int $amount_due
 * @property int $amount_paid
 * @property string $currency
 * @property string $status
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property string|null $pdf_url
 * @property string|null $hosted_url
 * @property Carbon|null $paid_at
 */
#[Fillable(['organization_id', 'stripe_invoice_id', 'number', 'amount_due', 'amount_paid', 'currency', 'status', 'period_start', 'period_end', 'pdf_url', 'hosted_url', 'paid_at'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function formattedTotal(): string
    {
        return number_format($this->amount_due / 100, 2);
    }
}
