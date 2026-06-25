<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// tracking_number is stored in existing shipping_resi column
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_status')->nullable()->after('shipping_etd');
            $table->string('tracking_status')->nullable()->after('fulfillment_status');
            $table->string('fulfillment_reference_id')->nullable()->after('tracking_status');
            $table->string('fulfillment_api_order_id')->nullable()->after('fulfillment_reference_id');
            $table->string('label_url')->nullable()->after('fulfillment_api_order_id');
            $table->json('fulfillment_payload')->nullable()->after('label_url');
            $table->timestamp('shipped_email_sent_at')->nullable()->after('fulfillment_payload');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status',
                'tracking_status',
                'fulfillment_reference_id',
                'fulfillment_api_order_id',
                'label_url',
                'fulfillment_payload',
                'shipped_email_sent_at',
            ]);
        });
    }
};
