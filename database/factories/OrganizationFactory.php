<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'personal' => false,
            'settings' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * An invisible personal organization (single-user mode).
     */
    public function personal(): static
    {
        return $this->state(fn (): array => ['personal' => true]);
    }
}
