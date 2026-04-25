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
        Schema::create('mistake_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_type')->index();
            $table->string('mistake_type')->index();
            $table->text('user_answer')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('note')->nullable();
            $table->string('review_status')->default('new');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'review_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mistake_journals');
    }
};
