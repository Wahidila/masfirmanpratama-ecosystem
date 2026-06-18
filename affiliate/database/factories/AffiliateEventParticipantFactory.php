<?php

namespace Database\Factories;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventParticipant;
use App\Models\Affiliator;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliateEventParticipantFactory extends Factory
{
    protected $model = AffiliateEventParticipant::class;

    public function definition(): array
    {
        return [
            'affiliate_event_id' => AffiliateEvent::factory(),
            'affiliator_id' => Affiliator::factory(),
            'score' => 0,
            'rank' => null,
            'progress' => null,
        ];
    }
}
