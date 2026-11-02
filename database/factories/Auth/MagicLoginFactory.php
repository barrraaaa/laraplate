<?php

namespace Database\Factories\Auth;

use App\Modules\Auth\Models\MagicLogin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MagicLogin>
 */
class MagicLoginFactory extends Factory
{
    protected $model = MagicLogin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'token_hash' => hash('sha256', fake()->sha256()),
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
