<?php

namespace App\Modules\Billing\Models;

use App\Models\Organization;
use Database\Factories\Billing\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Coupon (PRD 5.5 P1). Discounts are expressed either as percent_off or
 * amount_off; stripe_coupon_id links the matching Stripe-side coupon for
 * checkout sessions. Redemptions are unique per (coupon, organization).
 *
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property int|null $percent_off
 * @property int|null $amount_off
 * @property string|null $stripe_coupon_id
 * @property int|null $max_redemptions
 * @property Carbon|null $expires_at
 * @property bool $active
 * @property-read Collection<int, Organization> $redeemedBy
 */
#[Fillable(['code', 'description', 'percent_off', 'amount_off', 'stripe_coupon_id', 'max_redemptions', 'expires_at', 'active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Module factories are not auto-discoverable by the default path
     * guesser — resolve explicitly.
     */
    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'amount_off' => 'integer',
            'max_redemptions' => 'integer',
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Organization, $this> */
    public function redeemedBy(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'coupon_redemptions')->withTimestamps();
    }

    public function redemptionsCount(): int
    {
        return $this->redeemedBy()->count();
    }

    public function isEligible(Organization $organization): bool
    {
        return $this->active
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && ($this->max_redemptions === null || $this->redemptionsCount() < $this->max_redemptions)
            && ! $this->redeemedBy()->whereKey($organization->id)->exists();
    }

    public function summary(): string
    {
        if ($this->percent_off !== null) {
            return "{$this->percent_off}% off";
        }

        if ($this->amount_off !== null) {
            return '$'.number_format($this->amount_off / 100, 2).' off';
        }

        return 'Value';
    }
}
