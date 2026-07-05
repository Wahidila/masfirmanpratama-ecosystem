<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliator_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // alumni, non-alumni
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('benefits')->nullable();
            $table->decimal('default_commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliator_types');
    }
};
