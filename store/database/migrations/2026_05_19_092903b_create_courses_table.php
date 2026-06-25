<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->string('image_path')->nullable();
            $table->string('badge')->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('category_label')->nullable();
            $table->string('rating')->nullable();
            $table->string('student_count')->nullable();
            $table->text('tagline')->nullable();
            $table->boolean('installment_available')->default(true);
            $table->json('description')->nullable();
            $table->json('syllabus')->nullable();
            $table->json('schedule')->nullable();
            $table->json('benefits')->nullable();
            $table->json('testimonials')->nullable();
            $table->json('related')->nullable();
            $table->json('meta_seo')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
