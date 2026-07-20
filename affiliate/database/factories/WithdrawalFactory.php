<?php

namespace Database\Factories;

use App\Models\Affiliator;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        $amount = 100000;

        return [
            'affiliator_id' => Affiliator::factory(),
            'withdrawal_method_id' => WithdrawalMethod::factory(),
            'method_name' => fn (array $attributes) => WithdrawalMethod::find($attributes['withdrawal_method_id'])?->name,
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_note' => 'Nomor rekening tidak sesuai.',
            'processed_at' => now(),
        ]);
    }
}
