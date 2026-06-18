<?php

namespace Database\Factories;

use App\Models\AffiliateEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliateEventFactory extends Factory
{
    protected $model = AffiliateEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => 'challenge',
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(7),
            'rules' => ['min_orders' => 1],
            'rewards' => ['first' => 'Rp 500.000'],
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(7),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(1),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(14),
        ]);
    }
}
