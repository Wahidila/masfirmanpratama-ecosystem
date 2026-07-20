<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pindahkan data bank teks-bebas di profil affiliator menjadi rekening tersimpan.
 *
 * Pencocokan sengaja case-insensitive: satu-satunya baris di produksi berbunyi
 * "DANA" sedangkan metodenya bernama "Dana". Yang tidak cocok dengan metode mana
 * pun dilewati — affiliator menambahkan sendiri lewat halaman profil. Kolom bank_*
 * lama tidak dihapus, jadi tidak ada data yang hilang kalau pencocokan meleset.
 */
return new class extends Migration
{
    public function up(): void
    {
        $methods = DB::table('withdrawal_methods')
            ->get(['id', 'name'])
            ->keyBy(fn ($method) => mb_strtolower(trim($method->name)));

        if ($methods->isEmpty()) {
            return;
        }

        $affiliators = DB::table('affiliators')
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->whereNotNull('bank_account_number')
            ->where('bank_account_number', '!=', '')
            ->get(['id', 'name', 'bank_name', 'bank_account_number', 'bank_account_name']);

        foreach ($affiliators as $affiliator) {
            $method = $methods->get(mb_strtolower(trim($affiliator->bank_name)));

            if (! $method) {
                continue;
            }

            $exists = DB::table('affiliator_payout_accounts')
                ->where('affiliator_id', $affiliator->id)
                ->where('withdrawal_method_id', $method->id)
                ->where('account_number', $affiliator->bank_account_number)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('affiliator_payout_accounts')->insert([
                'affiliator_id' => $affiliator->id,
                'withdrawal_method_id' => $method->id,
                'account_number' => mb_substr(trim($affiliator->bank_account_number), 0, 50),
                // Nama pemilik boleh kosong di data lama; jatuh ke nama affiliator.
                'account_name' => mb_substr(trim($affiliator->bank_account_name ?: $affiliator->name), 0, 100),
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data asli tetap ada di kolom bank_* affiliators, jadi cukup kosongkan.
        DB::table('affiliator_payout_accounts')->delete();
    }
};
