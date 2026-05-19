<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPayment>
 */
class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->numberBetween(100_000, 2_000_000),
            'paid_at' => now()->subHours($this->faker->numberBetween(1, 72)),
            'method' => 'transfer',
            'proof_path' => null,
            'verified_at' => null,
            'verified_by' => null,
            'status' => 'pending',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
