<?php

namespace Database\Factories\Billing;

use App\Models\Organization;
use App\Modules\Billing\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'stripe_event_id' => 'evt_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'event_type' => 'invoice.payment_succeeded',
            'amount' => fake()->numberBetween(500, 20000),
            'currency' => 'usd',
            'status' => 'succeeded',
            'payload' => [],
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'event_type' => 'invoice.payment_failed',
            'status' => 'failed',
            'amount' => 0,
        ]);
    }
}
