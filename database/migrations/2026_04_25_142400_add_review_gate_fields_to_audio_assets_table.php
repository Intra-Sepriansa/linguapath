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
        Schema::table('audio_assets', function (Blueprint $table) {
            $table->timestamp('transcript_reviewed_at')->nullable()->after('transcript');
            $table->timestamp('approved_at')->nullable()->after('transcript_reviewed_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('approved_by');

            $table->index(
                ['status', 'is_real_audio', 'approved_at', 'transcript_reviewed_at'],
                'audio_assets_exam_ready_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_assets', function (Blueprint $table) {
            $table->dropIndex('audio_assets_exam_ready_idx');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['transcript_reviewed_at', 'approved_at', 'review_notes']);
        });
    }
};
