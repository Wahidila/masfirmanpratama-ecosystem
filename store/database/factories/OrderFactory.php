<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = $this->faker->numberBetween(150_000, 5_000_000);

        return [
            'order_number' => 'MFP-'.strtoupper(Str::random(8)),
            'status' => 'pending',
            'customer_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '08'.$this->faker->numerify('##########'),
            'address' => $this->faker->address(),
            'total' => $total,
            'ref_code' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
