<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peserta kursus — roster kelas.
 *
 * Diisi otomatis saat order kelas punya pembayaran terverifikasi (lunas ATAU
 * cicilan berjalan), dan bisa ditambah manual oleh admin (peserta offline).
 * order_id unique supaya satu order tidak jadi peserta ganda (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained('orders')->nullOnDelete();

            $table->string('name', 120);
            $table->string('email', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->text('motivation')->nullable();

            // Status keikutsertaan (dikelola admin).
            $table->enum('status', ['registered', 'active', 'graduated', 'cancelled'])->default('registered');
            // Status pembayaran: 'cicil' = cicilan berjalan, 'lunas' = lunas.
            $table->enum('payment_status', ['cicil', 'lunas'])->default('lunas');

            $table->text('notes')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_participants');
    }
};
