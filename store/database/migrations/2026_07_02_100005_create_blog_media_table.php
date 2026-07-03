<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_media', function (Blueprint $table) {
            $table->id();
            // Original WordPress attachment post id — remap key for _thumbnail_id +
            // download-once idempotency.
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('disk_path');            // e.g. blog/uploads/2026/06/foo.jpg (public disk)
            $table->string('original_url')->nullable();  // source wp-content/uploads URL
            $table->string('original_path')->nullable(); // relative uploads path (_wp_attached_file)
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_media');
    }
};
