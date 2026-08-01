<?php

namespace Database\Factories;

use App\Models\PromoBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoBanner>
 */
class PromoBannerFactory extends Factory
{
    protected $model = PromoBanner::class;

    public function definition(): array
    {
        return [
            'title' => 'Banner '.$this->faker->words(3, true),
            'image_path' => 'assets/images/jadwal-amc-surabaya.webp',
            'link_url' => 'https://wa.me/6281230633464',
            'active' => true,
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    /** Jendela tayang sudah lewat (banner event kedaluwarsa). */
    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->subDay(),
        ]);
    }

    /** Belum masuk jendela tayang (dijadwalkan tayang nanti). */
    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addDay(),
            'ends_at' => null,
        ]);
    }
}
