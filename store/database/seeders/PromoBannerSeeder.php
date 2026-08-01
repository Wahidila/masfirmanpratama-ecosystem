<?php

namespace Database\Seeders;

use App\Models\PromoBanner;
use Illuminate\Database\Seeder;

/**
 * Seed banner jadwal terdekat yang sebelumnya hardcode di home.blade.php,
 * supaya homepage tidak berubah saat section jadi dinamis.
 */
class PromoBannerSeeder extends Seeder
{
    public function run(): void
    {
        PromoBanner::updateOrCreate(
            ['image_path' => 'assets/images/jadwal-amc-surabaya.webp'],
            [
                'title' => 'Kelas Reguler Alpha Mind Control — Surabaya 23 Mei 2026 di Hotel Bisanta bersama Mas Firman',
                'link_url' => 'https://wa.me/6281230633464?text=Saya%20mau%20daftar%20Kelas%20Reguler%20AMC%20Surabaya%2023%20Mei%202026',
                'active' => true,
                'sort_order' => 0,
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
