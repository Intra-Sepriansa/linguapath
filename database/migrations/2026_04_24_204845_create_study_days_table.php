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
        Schema::create('study_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_path_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title');
            $table->string('focus_skill')->index();
            $table->text('objective');
            $table->unsignedSmallInteger('estimated_minutes')->default(90);
            $table->timestamps();

            $table->unique(['study_path_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_days');
    }
};
