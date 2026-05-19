<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);
        $slug = Str::slug($title).'-'.Str::lower(Str::random(4));

        return [
            'title' => $title,
            'slug' => $slug,
            'type' => $this->faker->randomElement(['book', 'course']),
            'price' => $this->faker->numberBetween(50000, 500000),
            'stock' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['draft', 'active', 'archived']),
            'image_path' => null,
            'description' => $this->faker->paragraph(),
            'meta_seo' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function book(): static
    {
        return $this->state(fn () => ['type' => 'book']);
    }

    public function course(): static
    {
        return $this->state(fn () => ['type' => 'course']);
    }
}
