<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 3);
        $unitPrice = $this->faker->numberBetween(50_000, 1_000_000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => $qty * $unitPrice,
        ];
    }
}
