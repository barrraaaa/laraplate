<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'monthly_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'yearly_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'features' => [
                'features' => ['projects' => true],
                'quotas' => ['projects.max' => 20, 'storage.mb' => 5120],
            ],
            'active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Starter',
            'slug' => 'free',
            'monthly_price_id' => null,
            'yearly_price_id' => null,
            'features' => [
                'features' => ['projects' => true],
                'quotas' => ['projects.max' => 3, 'storage.mb' => 100],
            ],
        ]);
    }
}
