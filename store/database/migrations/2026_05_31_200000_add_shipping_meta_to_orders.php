<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_service')->nullable()->after('shipping_courier');
            $table->unsignedInteger('shipping_cost')->default(0)->after('shipping_service');
            $table->string('shipping_etd')->nullable()->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_service', 'shipping_cost', 'shipping_etd']);
        });
    }
};
