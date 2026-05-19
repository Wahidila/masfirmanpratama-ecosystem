<?php

namespace Database\Factories;

use App\Models\InstallmentScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallmentScheme>
 */
class InstallmentSchemeFactory extends Factory
{
    protected $model = InstallmentScheme::class;

    public function definition(): array
    {
        $n = $this->faker->numberBetween(1, 12);

        return [
            'product_id' => null,
            'name' => $n.'x Cicilan',
            'dp_pct' => $this->faker->numberBetween(0, 100),
            'n_installments' => $n,
            'interval_days' => 30,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function global(): static
    {
        return $this->state(fn () => ['product_id' => null]);
    }
}
