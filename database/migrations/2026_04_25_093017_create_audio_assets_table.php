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
        Schema::create('audio_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('audio_url')->nullable();
            $table->text('transcript');
            $table->text('speaker_notes')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->string('accent')->default('american');
            $table->decimal('speed', 3, 2)->default(1.00);
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_assets');
    }
};
