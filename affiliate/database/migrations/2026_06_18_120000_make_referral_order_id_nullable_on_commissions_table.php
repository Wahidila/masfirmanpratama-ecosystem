<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat referral_order_id nullable di tabel commissions.
     *
     * Diperlukan agar bonus_commission (reward gamifikasi) bisa membuat
     * Commission tanpa referral_order — komisi bonus tidak berasal dari order.
     */
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('referral_order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Catatan: down() dibiarkan no-op untuk menghindari kegagalan saat
        // masih ada baris commission dengan referral_order_id null (bonus).
        // Membalikkan ke NOT NULL akan error jika data bonus sudah ada.
    }
};
