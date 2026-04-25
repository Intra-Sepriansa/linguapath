<?php

namespace App\Services;

use App\Enums\MistakeType;
use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Models\MistakeJournal;
use App\Models\PracticeAnswer;
use App\Models\User;

class MistakeJournalService
{
    public function recordForAnswer(User $user, PracticeAnswer $answer): ?MistakeJournal
    {
        $answer->loadMissing(['question.correctOption', 'selectedOption']);

        if ($answer->is_correct || ! $answer->selectedOption) {
            return null;
        }

        return MistakeJournal::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'practice_answer_id' => $answer->id,
            ],
            [
                'question_id' => $answer->question_id,
                'section_type' => $answer->question->section_type,
                'mistake_type' => $this->mistakeTypeFor($answer->question->section_type),
                'user_answer' => $answer->selectedOption->option_text,
                'correct_answer' => $answer->question->correctOption?->option_text,
                'note' => $answer->question->explanation,
                'skill_tag_id' => $answer->question->skill_tag_id,
                'why_wrong' => $answer->question->why_wrong,
                'why_correct' => $answer->question->why_correct ?? $answer->question->explanation,
                'review_status' => ReviewStatus::New,
                'next_review_at' => now()->addDay(),
            ]
        );
    }

    public function mark(MistakeJournal $mistake, ReviewStatus $status): MistakeJournal
    {
        $mistake->update([
            'review_status' => $status,
            'reviewed_at' => $status === ReviewStatus::New ? null : now(),
            'next_review_at' => match ($status) {
                ReviewStatus::New => now()->addDay(),
                ReviewStatus::Reviewing => now()->addDays(2),
                ReviewStatus::RetestReady => now(),
                ReviewStatus::Resolved, ReviewStatus::Fixed => null,
            },
        ]);

        return $mistake->refresh();
    }

    private function mistakeTypeFor(SkillType $skill): MistakeType
    {
        return match ($skill) {
            SkillType::Listening => MistakeType::Listening,
            SkillType::Reading => MistakeType::Reading,
            SkillType::Vocabulary => MistakeType::Vocabulary,
            default => MistakeType::Grammar,
        };
    }
}
