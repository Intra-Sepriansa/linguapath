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
        Schema::create('writing_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('prompt_type')->index();
            $table->string('skill_level')->default('beginner')->index();
            $table->text('prompt');
            $table->unsignedSmallInteger('suggested_minutes')->default(10);
            $table->unsignedSmallInteger('min_words')->default(80);
            $table->json('rubric')->nullable();
            $table->text('sample_response')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_prompts');
    }
};
