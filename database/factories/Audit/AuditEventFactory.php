<?php

namespace Database\Factories\Audit;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
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
            'actor_id' => User::factory(),
            'action' => fake()->randomElement(['auth.login', 'organization.member_joined', 'billing.payment_succeeded']),
            'metadata' => ['actor_name' => fake()->name(), 'actor_email' => fake()->safeEmail()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
