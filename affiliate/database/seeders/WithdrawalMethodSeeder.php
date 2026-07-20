<?php

namespace Database\Seeders;

use App\Models\WithdrawalMethod;
use Illuminate\Database\Seeder;

class WithdrawalMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'BCA', 'type' => 'bank_transfer', 'min_withdrawal' => 50000],
            ['name' => 'BNI', 'type' => 'bank_transfer', 'min_withdrawal' => 50000],
            ['name' => 'BRI', 'type' => 'bank_transfer', 'min_withdrawal' => 50000],
            ['name' => 'Mandiri', 'type' => 'bank_transfer', 'min_withdrawal' => 50000],
            ['name' => 'BSI', 'type' => 'bank_transfer', 'min_withdrawal' => 50000],
            ['name' => 'Dana', 'type' => 'e_wallet', 'min_withdrawal' => 25000],
            ['name' => 'OVO', 'type' => 'e_wallet', 'min_withdrawal' => 25000],
            ['name' => 'GoPay', 'type' => 'e_wallet', 'min_withdrawal' => 25000],
            ['name' => 'ShopeePay', 'type' => 'e_wallet', 'min_withdrawal' => 25000],
        ];

        // firstOrCreate, bukan updateOrCreate: minimum dan biaya tiap metode kini
        // diatur admin lewat panel. updateOrCreate akan mengembalikannya ke angka
        // di bawah setiap kali seeder ikut jalan setelah migrasi.
        foreach ($methods as $method) {
            WithdrawalMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
