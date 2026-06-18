<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD', 'admin123');
        $email = env('ADMIN_SEED_EMAIL', 'admin@masfirmanpratama.com');

        // Hard-gate produksi: tolak password default agar tidak bocor ke live.
        if (app()->environment('production') && $password === 'admin123') {
            throw new \RuntimeException(
                'ADMIN_SEED_PASSWORD wajib di-set (bukan default) saat APP_ENV=production. '
                .'Set di .env sebelum menjalankan seeder.'
            );
        }

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin Firman Pratama',
                'password' => $password,
            ],
        );
    }
}
