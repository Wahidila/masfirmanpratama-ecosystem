<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_orders', function (Blueprint $table) {
            // Identitas pembeli dari webhook — dipakai untuk deteksi self-referral & audit.
            $table->string('buyer_email')->nullable()->after('buyer_name');
        });
    }

    public function down(): void
    {
        Schema::table('referral_orders', function (Blueprint $table) {
            $table->dropColumn('buyer_email');
        });
    }
};
