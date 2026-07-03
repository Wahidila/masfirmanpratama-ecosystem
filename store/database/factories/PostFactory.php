<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(6), '.');
        $slug = Str::slug($title).'-'.Str::lower(Str::random(4));
        $body = collect(range(1, 4))
            ->map(fn () => '<p>'.$this->faker->paragraph(5).'</p>')
            ->implode("\n");

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->faker->sentence(14),
            'content' => $body,
            'image_path' => null,
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'meta_seo' => null,
            'reading_minutes' => Post::estimateReadingMinutes($body),
            'views' => $this->faker->numberBetween(0, 5000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => 'scheduled',
            'published_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }
}
