<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cicilan hanya untuk KELAS/KURSUS, tidak untuk produk/buku (checkout produk
 * lunas-only). Kolom product_id + relasinya dead weight → dihapus. Skema kini
 * cuma bisa global (course_id null = semua kelas) atau di-scope ke satu kelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_schemes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('installment_schemes', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')
                ->constrained('products')->nullOnDelete();
        });
    }
};
