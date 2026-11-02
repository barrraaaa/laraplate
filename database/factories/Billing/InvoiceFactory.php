<?php

namespace Database\Factories\Billing;

use App\Models\Organization;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(900, 20000);
        $paid = fake()->boolean(85);

        return [
            'organization_id' => Organization::factory(),
            'stripe_invoice_id' => 'in_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
            'amount_due' => $amount,
            'amount_paid' => $paid ? $amount : 0,
            'currency' => 'usd',
            'status' => $paid ? 'paid' : 'open',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'pdf_url' => 'https://pay.stripe.com/invoice/example',
            'paid_at' => $paid ? now() : null,
        ];
    }
}
