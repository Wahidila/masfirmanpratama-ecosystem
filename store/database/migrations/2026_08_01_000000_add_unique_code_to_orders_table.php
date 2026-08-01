<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode unik pembayaran: angka kecil (1–999) yang ditambahkan ke total transfer
 * agar tiap order punya nominal transfer yang khas → admin gampang mencocokkan
 * transfer masuk dengan order-nya. Nullable: order lama = tanpa kode unik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('unique_code')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('unique_code');
        });
    }
};
