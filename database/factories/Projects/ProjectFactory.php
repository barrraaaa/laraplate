<?php

namespace Database\Factories\Projects;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'created_by' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'status' => ProjectStatus::Active->value,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Paused->value]);
    }
}
