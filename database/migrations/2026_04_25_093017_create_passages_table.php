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
        Schema::create('passages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('topic')->nullable();
            $table->text('body');
            $table->unsignedSmallInteger('word_count')->default(0);
            $table->string('difficulty')->default('intermediate')->index();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passages');
    }
};
