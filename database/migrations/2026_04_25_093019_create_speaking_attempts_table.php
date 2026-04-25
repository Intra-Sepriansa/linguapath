<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('speaking_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaking_prompt_id')->constrained()->cascadeOnDelete();
            $table->string('recording_path')->nullable();
            $table->text('transcript')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->unsignedSmallInteger('word_count')->default(0);
            $table->unsignedSmallInteger('filler_word_count')->default(0);
            $table->unsignedTinyInteger('pronunciation_score')->default(0);
            $table->unsignedTinyInteger('fluency_score')->default(0);
            $table->unsignedTinyInteger('grammar_score')->default(0);
            $table->unsignedTinyInteger('vocabulary_score')->default(0);
            $table->unsignedTinyInteger('confidence_score')->default(0);
            $table->unsignedTinyInteger('self_rating')->nullable();
            $table->json('feedback')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_attempts');
    }
};
