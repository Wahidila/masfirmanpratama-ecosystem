<?php

namespace Database\Factories;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventReward;
use App\Models\Affiliator;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliateEventRewardFactory extends Factory
{
    protected $model = AffiliateEventReward::class;

    public function definition(): array
    {
        return [
            'affiliate_event_id' => AffiliateEvent::factory(),
            'affiliator_id' => Affiliator::factory(),
            'reward_type' => 'cash',
            'reward_value' => 500000,
            'description' => 'Juara 1',
            'is_claimed' => false,
            'claimed_at' => null,
        ];
    }

    public function claimed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);
    }
}
