<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_event_id')->constrained('affiliate_events')->cascadeOnDelete();
            $table->foreignId('affiliator_id')->constrained('affiliators')->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->integer('rank')->nullable();
            $table->json('progress')->nullable();
            $table->timestamps();
            $table->unique(['affiliate_event_id', 'affiliator_id'], 'event_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_event_participants');
    }
};
