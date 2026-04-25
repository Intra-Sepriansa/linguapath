<?php

namespace App\Services;

use App\Enums\PracticeMode;
use App\Models\PracticeSession;
use App\Models\StudyDay;
use App\Models\StudyLog;
use App\Models\StudyPath;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StudyPathService
{
    public function activePath(): StudyPath
    {
        return StudyPath::query()
            ->where('is_active', true)
            ->with(['studyDays.lesson'])
            ->firstOrFail();
    }

    public function currentDay(User $user): ?StudyDay
    {
        $completedDayIds = $this->completedDayIds($user);

        return StudyDay::query()
            ->whereBelongsTo($this->activePath())
            ->whereNotIn('id', $completedDayIds)
            ->with('lesson')
            ->orderBy('day_number')
            ->first()
            ?? StudyDay::query()
                ->whereBelongsTo($this->activePath())
                ->with('lesson')
                ->orderByDesc('day_number')
                ->first();
    }

    /**
     * @return array<int>
     */
    public function completedDayIds(User $user): array
    {
        return StudyLog::query()
            ->whereBelongsTo($user)
            ->where('completed_lessons', '>', 0)
            ->whereNotNull('study_day_id')
            ->pluck('study_day_id')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pathForUser(User $user): array
    {
        $completedDayIds = collect($this->completedDayIds($user));
        $assessments = $this->assessmentMap($user);

        return $this->activePath()
            ->studyDays
            ->map(fn (StudyDay $day): array => $this->dayPayload($day, $completedDayIds->contains($day->id), $assessments[$day->id] ?? null))
            ->all();
    }

    public function complete(User $user, StudyDay $studyDay): StudyLog
    {
        $session = PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($studyDay)
            ->where('mode', PracticeMode::Lesson)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();

        if (! $session || ! $this->sessionPassed($session)) {
            throw ValidationException::withMessages([
                'study_day' => 'Mini-test must be perfect before this day can be marked as passed.',
            ]);
        }

        return StudyLog::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'study_day_id' => $studyDay->id,
            ],
            [
                'minutes_spent' => max((int) ceil($session->duration_seconds / 60), 10),
                'completed_lessons' => 1,
                'completed_questions' => $session->total_questions,
                'accuracy' => $session->score,
                'log_date' => today(),
            ]
        );
    }

    /**
     * @param  Collection<int, StudyDay>  $days
     * @param  array<int>  $completedDayIds
     * @param  array<int, array<string, mixed>>  $assessments
     * @return array<int, array<string, mixed>>
     */
    public function weekGroups(Collection $days, array $completedDayIds, array $assessments = []): array
    {
        return $days
            ->groupBy(fn (StudyDay $day): int => (int) ceil($day->day_number / 7))
            ->map(fn (Collection $weekDays, int $week): array => [
                'week' => $week,
                'title' => 'Week '.$week,
                'days' => $weekDays
                    ->map(fn (StudyDay $day): array => $this->dayPayload($day, in_array($day->id, $completedDayIds, true), $assessments[$day->id] ?? null))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function assessmentMap(User $user): array
    {
        return PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereNotNull('study_day_id')
            ->where('mode', PracticeMode::Lesson)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('study_day_id')
            ->map(function ($sessions): array {
                /** @var PracticeSession $session */
                $session = $sessions->first();
                $passed = $this->sessionPassed($session);

                return [
                    'status' => $passed ? 'passed' : 'failed',
                    'label' => $passed ? 'Lulus' : 'Tidak lulus',
                    'score' => (int) round((float) $session->score),
                    'correct_answers' => $session->correct_answers,
                    'total_questions' => $session->total_questions,
                    'passing_score' => 100,
                    'requires_all_correct' => true,
                    'attempted_at' => $session->finished_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $assessment
     * @return array<string, mixed>
     */
    public function dayPayload(StudyDay $day, bool $completed, ?array $assessment = null): array
    {
        return [
            'id' => $day->id,
            'day_number' => $day->day_number,
            'title' => $day->title,
            'focus_skill' => $day->focus_skill->value,
            'focus_label' => $day->focus_skill->label(),
            'objective' => $day->objective,
            'estimated_minutes' => $day->estimated_minutes,
            'completed' => $completed,
            'lesson_id' => $day->lesson?->id,
            'assessment' => $assessment ?? [
                'status' => 'pending',
                'label' => 'Belum tes',
                'score' => null,
                'correct_answers' => 0,
                'total_questions' => 0,
                'passing_score' => 100,
                'requires_all_correct' => true,
                'attempted_at' => null,
            ],
        ];
    }

    private function sessionPassed(PracticeSession $session): bool
    {
        return $session->total_questions > 0
            && $session->correct_answers === $session->total_questions
            && (float) $session->score >= 100.0;
    }
}
