<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setValue('store_info', [
            'name' => 'MasFirmanPratama.com',
            'tagline' => 'Mind Power & Life Mastery',
            'address' => 'Jl. Contoh No. 1, Jakarta — alamat dummy AMC (replace via admin form)',
            'phone' => '+62 812-3456-7890',
            'email' => 'cs@masfirmanpratama.com',
        ], 'json');

        Setting::setValue('bank_accounts', [
            [
                'bank' => 'BCA',
                'number' => '1234-5678-9012',
                'holder' => 'PT. Dummy AMC',
                'logo' => 'bca',
                'logo_color' => 'sky',
            ],
            [
                'bank' => 'Mandiri',
                'number' => '0987-6543-2109',
                'holder' => 'PT. Dummy AMC',
                'logo' => 'mandiri',
                'logo_color' => 'amber',
            ],
        ], 'json');

        Setting::setValue('wa_admin', [
            'number' => '6281234567890',
            'label' => 'Admin Firman Pratama',
        ], 'json');

        Setting::setValue('shipping_methods', [
            ['code' => 'REG', 'label' => 'JNE Reguler — 3 sd 5 hari', 'price' => 25000],
            ['code' => 'YES', 'label' => 'JNE YES — 1 hari', 'price' => 45000],
            ['code' => 'OKE', 'label' => 'JNE OKE — 5 sd 7 hari', 'price' => 18000],
        ], 'json');
    }
}
