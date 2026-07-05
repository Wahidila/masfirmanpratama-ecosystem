<?php

namespace Database\Seeders;

use App\Models\AffiliatorType;
use App\Models\CommissionSetting;
use Illuminate\Database\Seeder;

class CommissionSettingSeeder extends Seeder
{
    public function run(): void
    {
        $alumni = AffiliatorType::where('slug', 'alumni')->first();
        $nonAlumni = AffiliatorType::where('slug', 'non-alumni')->first();

        $settings = [
            // Alumni rates (komisi tertinggi)
            ['affiliator_type_id' => $alumni?->id, 'product_type' => 'course', 'rate' => 15.00, 'min_amount' => 0, 'cooling_days' => 7, 'is_active' => true],
            ['affiliator_type_id' => $alumni?->id, 'product_type' => 'book', 'rate' => 12.00, 'min_amount' => 0, 'cooling_days' => 7, 'is_active' => true],
            // Non-alumni rates (komisi standar)
            ['affiliator_type_id' => $nonAlumni?->id, 'product_type' => 'course', 'rate' => 10.00, 'min_amount' => 0, 'cooling_days' => 7, 'is_active' => true],
            ['affiliator_type_id' => $nonAlumni?->id, 'product_type' => 'book', 'rate' => 8.00, 'min_amount' => 0, 'cooling_days' => 7, 'is_active' => true],
            // Global fallback (tipe/produk apa pun yang tak ter-cover) — komisi minimum
            ['affiliator_type_id' => null, 'product_type' => null, 'rate' => 8.00, 'min_amount' => 0, 'cooling_days' => 7, 'is_active' => true],
        ];

        foreach ($settings as $setting) {
            // Idempotent: kunci unik = (affiliator_type_id, product_type)
            CommissionSetting::updateOrCreate(
                [
                    'affiliator_type_id' => $setting['affiliator_type_id'],
                    'product_type' => $setting['product_type'],
                ],
                $setting
            );
        }
    }
}
