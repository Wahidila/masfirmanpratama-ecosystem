<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\InstallmentScheme;
use Illuminate\Database\Seeder;

class InstallmentSchemeSeeder extends Seeder
{
    public function run(): void
    {
        // Skema global (course_id = null) berlaku untuk SEMUA kelas.
        InstallmentScheme::updateOrCreate(
            ['course_id' => null, 'name' => 'Lunas (cash)'],
            ['dp_pct' => 100, 'n_installments' => 1, 'interval_days' => 0, 'active' => true],
        );

        InstallmentScheme::updateOrCreate(
            ['course_id' => null, 'name' => '3x Cicilan'],
            ['dp_pct' => 30, 'n_installments' => 3, 'interval_days' => 30, 'active' => true],
        );

        InstallmentScheme::updateOrCreate(
            ['course_id' => null, 'name' => '6x Cicilan'],
            ['dp_pct' => 20, 'n_installments' => 6, 'interval_days' => 30, 'active' => true],
        );

        // Skema 12x untuk kelas reguler (high-ticket) — B1: pakai Course model.
        $kelas = Course::where('slug', 'kelas-amc-reguler')->first();
        if ($kelas) {
            InstallmentScheme::updateOrCreate(
                ['course_id' => $kelas->id, 'name' => '12x Cicilan (Kelas Reguler)'],
                ['dp_pct' => 15, 'n_installments' => 12, 'interval_days' => 30, 'active' => true],
            );
        }

        // Skema 12x untuk kelas privat (sync-c1)
        $privat = Course::where('slug', 'kelas-amc-privat')->first();
        if ($privat) {
            InstallmentScheme::updateOrCreate(
                ['course_id' => $privat->id, 'name' => '12x Cicilan (Kelas Privat)'],
                ['dp_pct' => 15, 'n_installments' => 12, 'interval_days' => 30, 'active' => true],
            );
        }

        // Skema 12x untuk kelas platinum (sync-c1)
        $platinum = Course::where('slug', 'kelas-amc-platinum')->first();
        if ($platinum) {
            InstallmentScheme::updateOrCreate(
                ['course_id' => $platinum->id, 'name' => '12x Cicilan (Kelas Platinum)'],
                ['dp_pct' => 20, 'n_installments' => 12, 'interval_days' => 30, 'active' => true],
            );
        }
    }
}
