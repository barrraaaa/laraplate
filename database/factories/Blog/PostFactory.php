<?php

namespace Database\Factories\Blog;

use App\Models\User;
use App\Modules\Blog\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(18),
            'body' => implode("\n\n", fake()->paragraphs(4)),
            'status' => 'draft',
            'published_at' => null,
            'meta_description' => fake()->sentence(12),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
