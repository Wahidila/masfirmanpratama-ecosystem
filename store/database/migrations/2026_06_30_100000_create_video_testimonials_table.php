<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('participant_name');
            $table->string('role')->nullable();
            $table->string('video_url', 2048);
            $table->string('poster_url', 2048)->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('active')->index();
            $table->boolean('show_on_homepage')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        $now = now();

        DB::table('video_testimonials')->insert([
            [
                'title' => 'Dari AMC Saya Sadar Hidup Ini Indah, Enak dan Menyenangkan',
                'participant_name' => 'Ria Handayani',
                'role' => 'Alumni AMC',
                'video_url' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/27-1.mp4',
                'poster_url' => null,
                'status' => 'active',
                'show_on_homepage' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Kita Bisa Mencapai Apapun dengan Kekuatan Pikiran',
                'participant_name' => 'Fitria',
                'role' => 'Alumni AMC',
                'video_url' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/bener-28-2.mp4',
                'poster_url' => null,
                'status' => 'active',
                'show_on_homepage' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'AMC Adalah Ilmu yang Sangat Mind Blowing',
                'participant_name' => 'Edi',
                'role' => 'Alumni AMC',
                'video_url' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/bener-1.mp4',
                'poster_url' => null,
                'status' => 'active',
                'show_on_homepage' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'AMC Adalah Ilmu yang "Daging" Banget',
                'participant_name' => 'Ane',
                'role' => 'Alumni AMC',
                'video_url' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/27-3.mp4',
                'poster_url' => null,
                'status' => 'active',
                'show_on_homepage' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('video_testimonials');
    }
};
