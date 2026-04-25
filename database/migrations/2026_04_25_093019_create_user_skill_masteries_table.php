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
        Schema::create('user_skill_masteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_tag_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('correct_attempts')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skill_masteries');
    }
};
