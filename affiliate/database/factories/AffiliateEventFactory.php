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
            'rewards' => [
                ['rank' => 1, 'reward_type' => 'cash', 'reward_value' => 500000, 'description' => 'Juara 1'],
                ['rank' => 2, 'reward_type' => 'cash', 'reward_value' => 300000, 'description' => 'Juara 2'],
                ['rank' => 3, 'reward_type' => 'cash', 'reward_value' => 100000, 'description' => 'Juara 3'],
            ],
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
