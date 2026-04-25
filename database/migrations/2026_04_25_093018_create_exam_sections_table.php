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
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_simulation_id')->constrained()->cascadeOnDelete();
            $table->string('section_type')->index();
            $table->unsignedTinyInteger('position');
            $table->string('status')->default('locked')->index();
            $table->unsignedSmallInteger('duration_seconds');
            $table->unsignedSmallInteger('total_questions');
            $table->unsignedSmallInteger('correct_answers')->default(0);
            $table->unsignedSmallInteger('estimated_scaled_score')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_simulation_id', 'section_type']);
            $table->index(['exam_simulation_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sections');
    }
};
