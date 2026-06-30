<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom desa/kelurahan (shipping_village).
 *
 * API ongkir Agenwebsite hanya turun sampai kecamatan (district) — tidak punya
 * data desa. Tapi alamat Indonesia lengkap butuh desa/kelurahan untuk kurir
 * mengantar. Kolom ini hanya untuk kelengkapan alamat (label kirim + admin),
 * BUKAN dipakai kalkulasi tarif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_village', 120)->nullable()->after('shipping_district');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_village');
        });
    }
};
