<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder khusus PRODUKSI — hanya data esensial, TANPA order/payment demo.
     *
     * Jalankan di server produksi dengan:
     *   php artisan db:seed --class=ProductionSeeder --force
     *
     * Perbedaan dengan DatabaseSeeder (dev):
     * - TIDAK menjalankan OrderSeeder (data order/pembayaran demo)
     * - Admin password WAJIB dari env ADMIN_SEED_PASSWORD (lihat AdminSeeder)
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,            // admin login (password dari env)
            SettingsSeeder::class,         // konfigurasi store k/v
            ProductSeeder::class,          // katalog buku
            CourseSeeder::class,           // katalog kelas
            InstallmentSchemeSeeder::class, // skema cicilan
            PromoBannerSeeder::class,      // banner jadwal terdekat homepage
        ]);
    }
}
