<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => OrganizationRole::Member->value,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => OrganizationRole::Owner->value]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => OrganizationRole::Admin->value]);
    }

    public function viewer(): static
    {
        return $this->state(fn (): array => ['role' => OrganizationRole::Viewer->value]);
    }
}
