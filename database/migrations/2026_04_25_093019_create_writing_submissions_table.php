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
        Schema::create('writing_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('writing_prompt_id')->constrained()->cascadeOnDelete();
            $table->text('response_text');
            $table->unsignedSmallInteger('word_count')->default(0);
            $table->unsignedTinyInteger('task_score')->default(0);
            $table->unsignedTinyInteger('grammar_score')->default(0);
            $table->unsignedTinyInteger('vocabulary_score')->default(0);
            $table->unsignedTinyInteger('coherence_score')->default(0);
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->json('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_submissions');
    }
};
