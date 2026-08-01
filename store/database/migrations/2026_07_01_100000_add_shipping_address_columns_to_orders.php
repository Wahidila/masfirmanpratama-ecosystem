<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add discrete shipping address columns + payment_url + index AWB lookup ids.
 *
 * Why: FulfillmentService::buildShipmentData() dulu reconstruct city/province
 * via explode(',', $order->address) — itu bukan inverse dari composeAddress()
 * (address_line bisa berisi koma → field shift). Sekarang kita simpan kolom
 * diskrit dari autocomplete shipping/data (canonical {province,city,district}).
 *
 * Plus index pada fulfillment_api_order_id + fulfillment_reference_id supaya
 * AWB callback lookup cepat + index hint kalau mau dijadikan unique nanti.
 *
 * Plus fulfillment_payment_url buat pending_payment shipments (admin perlu
 * link bayar ongkir; sebelumnya cuma muncul pesan "menunggu pembayaran" tanpa
 * URL → stuck forever).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_city', 120)->nullable()->after('address');
            $table->string('shipping_province', 120)->nullable()->after('shipping_city');
            $table->string('shipping_district', 120)->nullable()->after('shipping_province');
            $table->string('shipping_zipcode', 20)->nullable()->after('shipping_district');
            $table->string('fulfillment_payment_url', 500)->nullable()->after('label_url');

            $table->index('fulfillment_api_order_id', 'orders_fulfillment_api_order_id_idx');
            $table->index('fulfillment_reference_id', 'orders_fulfillment_reference_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_fulfillment_api_order_id_idx');
            $table->dropIndex('orders_fulfillment_reference_id_idx');
            $table->dropColumn([
                'shipping_city',
                'shipping_province',
                'shipping_district',
                'shipping_zipcode',
                'fulfillment_payment_url',
            ]);
        });
    }
};
