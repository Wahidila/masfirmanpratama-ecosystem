<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner promo/jadwal terdekat di homepage — dikelola admin (CRUD) karena
 * sering ganti (event kelas offline per kota/tanggal). Mendukung jendela
 * tayang (starts_at/ends_at) supaya banner event auto-hilang setelah lewat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // label admin + alt text gambar
            $table->string('image_path', 2048);
            $table->string('link_url', 2048)->nullable(); // target klik (mis. wa.me)
            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->dateTime('starts_at')->nullable(); // mulai tayang (null = langsung)
            $table->dateTime('ends_at')->nullable();   // akhir tayang (null = selamanya)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};
