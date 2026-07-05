<?php

namespace Database\Seeders;

use App\Models\AffiliatorType;
use Illuminate\Database\Seeder;

class AffiliatorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Alumni',
                'slug' => 'alumni',
                'description' => 'Alumni program AMC yang sudah lulus dan berpengalaman',
                'benefits' => ['Komisi lebih tinggi', 'Akses materi eksklusif', 'Priority support', 'Badge Alumni'],
                'default_commission_rate' => 15.00,
            ],
            [
                'name' => 'Non-Alumni',
                'slug' => 'non-alumni',
                'description' => 'Affiliator umum yang belum mengikuti program AMC',
                'benefits' => ['Komisi standar', 'Akses materi marketing dasar', 'Support via grup'],
                'default_commission_rate' => 10.00,
            ],
        ];

        foreach ($types as $type) {
            AffiliatorType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
