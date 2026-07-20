<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // amount tetap bruto (yang dipotong dari saldo). net_amount yang
            // benar-benar ditransfer ke affiliator.
            $table->decimal('fee', 12, 2)->default(0)->after('amount');
            $table->decimal('net_amount', 12, 2)->default(0)->after('fee');

            // Snapshot nama metode saat pengajuan. Nomor dan nama rekening sudah
            // di-snapshot sejak awal, tapi nama metode dibaca lewat relasi hidup —
            // begitu admin bisa me-rename metode, seluruh riwayat lama ikut berubah
            // bunyinya.
            $table->string('method_name')->nullable()->after('withdrawal_method_id');
        });

        // Baris lama belum mengenal biaya admin: neto sama dengan bruto.
        DB::table('withdrawals')->update(['net_amount' => DB::raw('amount')]);

        DB::statement(
            'update withdrawals set method_name = ('.
            'select name from withdrawal_methods where withdrawal_methods.id = withdrawals.withdrawal_method_id'.
            ') where method_name is null'
        );
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['fee', 'net_amount', 'method_name']);
        });
    }
};
