<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds the sample plan catalog from config/saas.php -> plans.catalog
 * (PRD 5.5 / demo data is explicitly config-driven and idempotent).
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array{name: string, slug: string, description?: string, monthly_price_id?: string|null, yearly_price_id?: string|null, features?: array<string, mixed>}> $catalog */
        $catalog = (array) config('saas.plans.catalog', []);

        foreach ($catalog as $index => $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name' => $planData['name'],
                    'description' => $planData['description'] ?? null,
                    'monthly_price_id' => $planData['monthly_price_id'] ?? null,
                    'yearly_price_id' => $planData['yearly_price_id'] ?? null,
                    'features' => $planData['features'] ?? null,
                    'active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
