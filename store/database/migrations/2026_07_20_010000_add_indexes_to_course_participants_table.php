<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index tambahan untuk daftar peserta berskala besar (ribuan baris).
 *
 * - joined_at: dipakai ORDER BY di halaman index; tanpa index -> filesort.
 * - name: membantu pencarian awalan & pengurutan nama.
 * - (course_id, payment_status): kombinasi filter yang sering dipakai bersama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_participants', function (Blueprint $table) {
            $table->index('joined_at');
            $table->index('name');
            $table->index(['course_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('course_participants', function (Blueprint $table) {
            $table->dropIndex(['joined_at']);
            $table->dropIndex(['name']);
            $table->dropIndex(['course_id', 'payment_status']);
        });
    }
};
