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
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_day_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_type')->index();
            $table->string('mode')->index();
            $table->unsignedSmallInteger('total_questions');
            $table->unsignedSmallInteger('correct_answers')->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
