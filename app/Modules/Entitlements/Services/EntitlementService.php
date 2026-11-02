<?php

namespace App\Modules\Entitlements\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Core\FeatureManager;
use App\Modules\Entitlements\Models\UsageRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centralized entitlement checks (PRD 5.6): feature flags, quotas, usage
 * recording and limit enforcement. One API used by policies, controllers
 * and modules.
 */
final class EntitlementService
{
    public function __construct(
        private BillingService $billing,
        private FeatureManager $features,
    ) {}

    public function plan(Organization $organization): Plan
    {
        return $this->billing->currentPlan($organization);
    }

    public function can(Organization $organization, string $feature): bool
    {
        if (! $this->enforcing()) {
            return true;
        }

        return $this->plan($organization)->featureFlag($feature);
    }

    public function quota(Organization $organization, string $metric): ?int
    {
        if (! $this->enforcing()) {
            return null;
        }

        return $this->plan($organization)->quota($metric);
    }

    public function usage(Organization $organization, string $metric): int
    {
        return (int) UsageRecord::query()
            ->where('organization_id', $organization->id)
            ->where('metric', $metric)
            ->whereDate('period_start', $this->periodStart()->toDateString())
            ->sum('quantity');
    }

    public function remaining(Organization $organization, string $metric): ?int
    {
        $quota = $this->quota($organization, $metric);

        if ($quota === null) {
            return null;
        }

        return max(0, $quota - $this->usage($organization, $metric));
    }

    public function overLimit(Organization $organization, string $metric, int $additional = 0): bool
    {
        if (! $this->enforcing()) {
            return false;
        }

        $quota = $this->quota($organization, $metric);

        if ($quota === null) {
            return false;
        }

        return $this->usage($organization, $metric) + $additional > $quota;
    }

    /**
     * Record consumption against the active organization and period.
     * Race-safe: the (organization, metric, period) row is locked.
     */
    public function record(Organization $organization, string $metric, int $quantity = 1): UsageRecord
    {
        return DB::transaction(function () use ($organization, $metric, $quantity): UsageRecord {
            $periodStart = $this->periodStart();
            $periodEnd = $this->periodEnd();

            // Carbon values (not date strings) so the WHERE matches the
            // datetime-formatted storage of the date-cast columns.
            $record = UsageRecord::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'metric' => $metric,
                        'period_start' => $periodStart,
                    ],
                    [
                        'period_end' => $periodEnd,
                        'quantity' => 0,
                    ],
                );

            $record->increment('quantity', max(0, $quantity));

            return $record->fresh();
        });
    }

    private function enforcing(): bool
    {
        return $this->features->enabled('billing') && $this->features->enabled('usage_limits');
    }

    private function periodStart(): Carbon
    {
        return match ((string) config('saas.usage.period', 'monthly')) {
            default => now()->startOfMonth(),
        };
    }

    private function periodEnd(): Carbon
    {
        return match ((string) config('saas.usage.period', 'monthly')) {
            default => now()->endOfMonth(),
        };
    }
}
