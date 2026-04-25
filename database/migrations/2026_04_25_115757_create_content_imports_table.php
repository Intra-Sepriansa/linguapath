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
        Schema::create('content_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind')->index();
            $table->string('source_path');
            $table->string('checksum', 64);
            $table->string('status')->default('completed')->index();
            $table->unsignedInteger('imported_records')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'source_path', 'checksum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_imports');
    }
};
