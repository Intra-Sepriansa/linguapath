<?php

namespace App\Models;

use App\Enums\MistakeType;
use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use Database\Factories\MistakeJournalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'question_id', 'skill_tag_id', 'practice_answer_id', 'exam_answer_id', 'section_type', 'mistake_type', 'user_answer', 'correct_answer', 'note', 'why_wrong', 'why_correct', 'personal_note', 'frequency', 'review_status', 'reviewed_at', 'next_review_at'])]
class MistakeJournal extends Model
{
    /** @use HasFactory<MistakeJournalFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function skillTag(): BelongsTo
    {
        return $this->belongsTo(SkillTag::class);
    }

    public function practiceAnswer(): BelongsTo
    {
        return $this->belongsTo(PracticeAnswer::class);
    }

    public function examAnswer(): BelongsTo
    {
        return $this->belongsTo(ExamAnswer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_type' => SkillType::class,
            'mistake_type' => MistakeType::class,
            'review_status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
            'next_review_at' => 'datetime',
        ];
    }
}
