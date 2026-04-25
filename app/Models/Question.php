<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['lesson_id', 'passage_id', 'audio_asset_id', 'skill_tag_id', 'section_type', 'question_type', 'difficulty', 'status', 'exam_eligible', 'question_text', 'audio_url', 'transcript', 'passage_text', 'explanation', 'evidence_sentence', 'why_correct', 'why_wrong', 'core_sentence'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    public const array STATUSES = [
        'draft',
        'ready',
        'published',
        'archived',
    ];

    public const array ACTIVE_STATUSES = [
        'ready',
        'published',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function skillTag(): BelongsTo
    {
        return $this->belongsTo(SkillTag::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('option_label');
    }

    public function correctOption(): HasOne
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }

    public function practiceAnswers(): HasMany
    {
        return $this->hasMany(PracticeAnswer::class);
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * @return list<string>
     */
    public function qualityWarnings(): array
    {
        $this->loadMissing(['options', 'audioAsset', 'passage', 'skillTag']);

        $warnings = [];
        $optionsCount = $this->options->count();
        $correctCount = $this->options->where('is_correct', true)->count();

        if (blank($this->explanation)) {
            $warnings[] = 'Missing explanation';
        }

        if (blank($this->difficulty)) {
            $warnings[] = 'Missing difficulty';
        }

        if ($this->question_type === null) {
            $warnings[] = 'Missing question type';
        }

        if ($this->skill_tag_id === null) {
            $warnings[] = 'Missing skill tag';
        }

        if ($this->section_type === SkillType::Reading && $this->passage_id === null) {
            $warnings[] = 'Reading question without passage';
        }

        if ($this->section_type === SkillType::Reading && blank($this->evidence_sentence)) {
            $warnings[] = 'Missing evidence sentence';
        }

        if ($this->section_type === SkillType::Listening && $this->audio_asset_id === null) {
            $warnings[] = 'Listening question without audio';
        }

        if ($this->section_type === SkillType::Listening && $this->audioAsset && ! $this->audioAsset->is_real_audio) {
            $warnings[] = 'Listening audio is transcript fallback';
        }

        if ($optionsCount !== 4) {
            $warnings[] = 'Invalid options count';
        }

        if ($correctCount === 0) {
            $warnings[] = 'No correct answer';
        }

        if ($correctCount > 1) {
            $warnings[] = 'Multiple correct answers';
        }

        return $warnings;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_type' => SkillType::class,
            'question_type' => QuestionType::class,
            'exam_eligible' => 'boolean',
        ];
    }
}
