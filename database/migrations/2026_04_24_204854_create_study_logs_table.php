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
        Schema::create('study_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_day_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('minutes_spent')->default(0);
            $table->unsignedSmallInteger('completed_lessons')->default(0);
            $table->unsignedSmallInteger('completed_questions')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->date('log_date')->index();
            $table->timestamps();

            $table->unique(['user_id', 'study_day_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_logs');
    }
};
