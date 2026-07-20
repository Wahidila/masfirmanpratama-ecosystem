<?php

namespace Database\Factories;

use App\Models\Affiliator;
use App\Models\AffiliatorPayoutAccount;
use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliatorPayoutAccountFactory extends Factory
{
    protected $model = AffiliatorPayoutAccount::class;

    public function definition(): array
    {
        return [
            'affiliator_id' => Affiliator::factory(),
            'withdrawal_method_id' => WithdrawalMethod::factory(),
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
