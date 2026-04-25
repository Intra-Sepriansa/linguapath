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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('password')->index();
        });

        Schema::table('audio_assets', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('audio_url');
            $table->string('mime_type')->nullable()->after('file_path');
            $table->unsignedInteger('file_size')->nullable()->after('mime_type');
            $table->foreignId('uploaded_by')->nullable()->after('file_size')->constrained('users')->nullOnDelete();
            $table->boolean('is_real_audio')->default(false)->after('uploaded_by');
            $table->unsignedTinyInteger('playback_limit_exam')->default(1)->after('is_real_audio');
            $table->string('status')->default('draft')->after('playback_limit_exam');

            $table->index(['status', 'is_real_audio']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->text('evidence_sentence')->nullable()->after('explanation');

            $table->index(['section_type', 'exam_eligible', 'difficulty']);
            $table->index(['passage_id', 'question_type']);
        });

        Schema::table('passages', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('source');
            $table->timestamp('reviewed_at')->nullable()->after('status');

            $table->index(['status', 'difficulty', 'word_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('passages', function (Blueprint $table) {
            $table->dropIndex(['status', 'difficulty', 'word_count']);
            $table->dropColumn(['status', 'reviewed_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['section_type', 'exam_eligible', 'difficulty']);
            $table->dropIndex(['passage_id', 'question_type']);
            $table->dropColumn('evidence_sentence');
        });

        Schema::table('audio_assets', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_real_audio']);
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['file_path', 'mime_type', 'file_size', 'is_real_audio', 'playback_limit_exam', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
