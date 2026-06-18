<?php

namespace Database\Factories;

use App\Models\Affiliator;
use App\Models\ReferralCode;
use App\Models\ReferralOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralOrderFactory extends Factory
{
    protected $model = ReferralOrder::class;

    public function definition(): array
    {
        return [
            'referral_code_id' => ReferralCode::factory(),
            'affiliator_id' => Affiliator::factory(),
            'store_order_id' => 'ORD-'.fake()->unique()->numerify('######'),
            'buyer_name' => fake()->name(),
            'order_total' => fake()->randomFloat(2, 100000, 1000000),
            'status' => 'paid',
            'ordered_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
