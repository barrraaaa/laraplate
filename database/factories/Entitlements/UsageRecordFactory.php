<?php

namespace Database\Factories\Entitlements;

use App\Models\Organization;
use App\Modules\Entitlements\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'metric' => fake()->randomElement(['projects', 'storage.mb', 'api.requests']),
            'quantity' => fake()->numberBetween(1, 200),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ];
    }
}
