<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom order_meta (JSON) untuk menyimpan data tambahan order
 * seperti occupation/motivation kelas. Ini membebaskan kolom ref_code
 * agar hanya dipakai untuk referral affiliate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('order_meta')->nullable()->after('ref_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_meta');
        });
    }
};
