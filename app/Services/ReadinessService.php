<?php

namespace App\Services;

use App\Enums\SkillType;
use App\Models\PracticeSession;
use App\Models\User;

class ReadinessService
{
    /**
     * @return array<string, int>
     */
    public function sectionAverages(User $user): array
    {
        return collect([SkillType::Listening, SkillType::Structure, SkillType::Reading])
            ->mapWithKeys(fn (SkillType $skill): array => [
                $skill->value => (int) round((float) PracticeSession::query()
                    ->whereBelongsTo($user)
                    ->where('section_type', $skill)
                    ->whereNotNull('finished_at')
                    ->avg('score')),
            ])
            ->all();
    }

    public function score(User $user): int
    {
        $averages = $this->sectionAverages($user);

        return (int) round(
            ($averages[SkillType::Structure->value] * 0.4)
            + ($averages[SkillType::Listening->value] * 0.3)
            + ($averages[SkillType::Reading->value] * 0.3)
        );
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Exam Ready',
            $score >= 75 => 'Ready',
            $score >= 60 => 'Improving',
            $score >= 40 => 'Basic',
            default => 'Beginner',
        };
    }
}
