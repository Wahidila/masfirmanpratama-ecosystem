<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('meta_seo')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->nullable();
            $table->unsignedBigInteger('views')->default(0);

            // WordPress migration / compatibility columns
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('wp_author_login')->nullable();
            $table->string('wp_guid')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('legacy_url')->nullable()->index();
            $table->foreignId('primary_category_id')->nullable()
                ->constrained('blog_categories')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
