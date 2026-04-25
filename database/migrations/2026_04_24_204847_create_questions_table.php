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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_type')->index();
            $table->string('question_type')->index();
            $table->string('difficulty')->default('beginner');
            $table->text('question_text');
            $table->string('audio_url')->nullable();
            $table->text('transcript')->nullable();
            $table->text('passage_text')->nullable();
            $table->text('explanation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
