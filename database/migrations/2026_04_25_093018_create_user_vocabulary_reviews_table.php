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
        Schema::create('user_vocabulary_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('learning')->index();
            $table->unsignedTinyInteger('user_difficulty_rating')->nullable();
            $table->unsignedSmallInteger('review_count')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->decimal('ease_score', 4, 2)->default(2.50);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'vocabulary_id']);
            $table->index(['user_id', 'status', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vocabulary_reviews');
    }
};
