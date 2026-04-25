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
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->timestamp('ends_at')->nullable()->after('started_at');
            $table->timestamp('submitted_at')->nullable()->after('finished_at');
            $table->string('submission_reason')->nullable()->after('submitted_at');

            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->dropIndex(['status', 'ends_at']);
            $table->dropColumn(['ends_at', 'submitted_at', 'submission_reason']);
        });
    }
};
