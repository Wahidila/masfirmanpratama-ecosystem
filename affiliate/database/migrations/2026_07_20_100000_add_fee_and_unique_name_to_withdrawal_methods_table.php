<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawal_methods', function (Blueprint $table) {
            // Biaya admin nominal tetap, dipotong dari yang diterima affiliator.
            // Dijaga selalu lebih kecil dari min_withdrawal di sisi validasi.
            $table->decimal('fee_flat', 12, 2)->default(0)->after('min_withdrawal');

            // Seeder memakai `name` sebagai kunci upsert tapi kolomnya tidak pernah
            // unique — dua metode bernama sama bisa dibuat lewat panel admin, dan
            // seed berikutnya akan memilih salah satunya secara acak.
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_methods', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn('fee_flat');
        });
    }
};
