<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // Self-referencing hierarchy (WordPress category_parent). Kept as a plain
            // indexed column (no hard FK) so import ordering never blocks on parents.
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            // Original WordPress term id — remap key for _yoast_wpseo_primary_category
            // + idempotent re-import.
            $table->unsignedBigInteger('wp_term_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
