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
        Schema::create('speaking_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('prompt_type')->index();
            $table->string('skill_level')->default('beginner')->index();
            $table->text('prompt');
            $table->text('sample_answer')->nullable();
            $table->json('focus_points')->nullable();
            $table->unsignedSmallInteger('preparation_seconds')->default(15);
            $table->unsignedSmallInteger('response_seconds')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_prompts');
    }
};
