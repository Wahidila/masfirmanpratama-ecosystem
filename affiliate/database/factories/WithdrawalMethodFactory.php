<?php

namespace Database\Factories;

use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalMethodFactory extends Factory
{
    protected $model = WithdrawalMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'type' => 'bank_transfer',
            'is_active' => true,
            'min_withdrawal' => 50000,
            'fee_flat' => 0,
        ];
    }

    public function eWallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'e_wallet',
            'min_withdrawal' => 25000,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withFee(float $fee): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_flat' => $fee,
        ]);
    }
}
