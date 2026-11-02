<?php

namespace Database\Factories\Billing;

use App\Modules\Billing\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SAVE-??-###'),
            'description' => fake()->sentence(4),
            'percent_off' => fake()->randomElement([10, 20, 30, 50]),
            'amount_off' => null,
            'stripe_coupon_id' => null,
            'max_redemptions' => null,
            'expires_at' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
