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
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('passage_id')->nullable()->after('lesson_id')->constrained()->nullOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->after('passage_id')->constrained()->nullOnDelete();
            $table->foreignId('skill_tag_id')->nullable()->after('audio_asset_id')->constrained()->nullOnDelete();
            $table->boolean('exam_eligible')->default(true)->after('difficulty');
            $table->text('why_correct')->nullable()->after('explanation');
            $table->text('why_wrong')->nullable()->after('why_correct');
            $table->text('core_sentence')->nullable()->after('why_wrong');
        });

        Schema::table('vocabularies', function (Blueprint $table) {
            $table->string('pronunciation')->nullable()->after('word');
            $table->unsignedInteger('frequency_rank')->nullable()->after('difficulty');
            $table->json('synonyms')->nullable()->after('frequency_rank');
            $table->json('antonyms')->nullable()->after('synonyms');
            $table->json('word_family')->nullable()->after('antonyms');
            $table->json('collocations')->nullable()->after('word_family');
        });

        Schema::table('mistake_journals', function (Blueprint $table) {
            $table->foreignId('exam_answer_id')->nullable()->after('practice_answer_id')->constrained()->nullOnDelete();
            $table->foreignId('skill_tag_id')->nullable()->after('question_id')->constrained()->nullOnDelete();
            $table->text('why_wrong')->nullable()->after('note');
            $table->text('why_correct')->nullable()->after('why_wrong');
            $table->text('personal_note')->nullable()->after('why_correct');
            $table->unsignedSmallInteger('frequency')->default(1)->after('personal_note');
            $table->timestamp('next_review_at')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mistake_journals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_answer_id');
            $table->dropConstrainedForeignId('skill_tag_id');
            $table->dropColumn(['why_wrong', 'why_correct', 'personal_note', 'frequency', 'next_review_at']);
        });

        Schema::table('vocabularies', function (Blueprint $table) {
            $table->dropColumn(['pronunciation', 'frequency_rank', 'synonyms', 'antonyms', 'word_family', 'collocations']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('passage_id');
            $table->dropConstrainedForeignId('audio_asset_id');
            $table->dropConstrainedForeignId('skill_tag_id');
            $table->dropColumn(['exam_eligible', 'why_correct', 'why_wrong', 'core_sentence']);
        });
    }
};
