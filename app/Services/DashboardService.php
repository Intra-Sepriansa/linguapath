<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Enums\VocabularyStatus;
use App\Models\MistakeJournal;
use App\Models\PracticeSession;
use App\Models\StudyLog;
use App\Models\StudyPath;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserVocabulary;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function __construct(
        private readonly StudyPathService $studyPath,
        private readonly ReadinessService $readiness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $path = $this->studyPath->activePath();
        $profile = UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $today = $this->studyPath->currentDay($user);
        $completedDayIds = $this->studyPath->completedDayIds($user);
        $completedDays = count($completedDayIds);
        $readinessScore = $this->readiness->score($user);
        $readinessTrend = $this->readinessTrend($user);
        $skillProgress = $this->readiness->sectionAverages($user);
        $weeklyActivity = $this->weeklyActivity($user);
        $studyLoad = $this->studyLoad($weeklyActivity, $profile);
        $vocabulary = $this->vocabularyStats($user);
        $mistakesToReview = MistakeJournal::query()
            ->whereBelongsTo($user)
            ->whereIn('review_status', [ReviewStatus::New, ReviewStatus::Reviewing])
            ->count();

        return [
            'profile' => [
                'target_score' => $profile->target_score,
                'daily_goal_minutes' => $profile->daily_goal_minutes,
                'current_level' => $profile->current_level,
                'exam_date' => $profile->exam_date?->toDateString(),
                'preferred_study_time' => $profile->preferred_study_time,
            ],
            'today' => $today ? $this->studyPath->dayPayload($today, in_array($today->id, $completedDayIds, true)) : null,
            'readiness' => [
                'score' => $readinessScore,
                'level' => $this->readiness->level($readinessScore),
                'trend' => $readinessTrend,
                'trend_label' => $this->trendLabel($readinessTrend),
                'estimated_toefl' => $this->estimatedToeflScore($readinessScore),
                'target_gap' => max(0, $profile->target_score - $this->estimatedToeflMidpoint($readinessScore)),
            ],
            'streak' => $this->streak($user),
            'completed_days' => $completedDays,
            'total_days' => $path->duration_days,
            'path' => $this->pathMetrics($user, $path, $completedDays),
            'study_load' => $studyLoad,
            'skill_progress' => $skillProgress,
            'skill_diagnostics' => $this->skillDiagnostics($user, $skillProgress),
            'weekly_activity' => $weeklyActivity,
            'mistakes_to_review' => $mistakesToReview,
            'mistake_heatmap' => $this->mistakeHeatmap($user),
            'vocabulary' => $vocabulary,
            'focus_queue' => $this->focusQueue($user, $skillProgress, $mistakesToReview, $vocabulary, $studyLoad),
            'upcoming_days' => $this->upcomingDays($path, $today?->day_number ?? 1, $completedDayIds),
            'recent_sessions' => $this->recentSessions($user),
            'next_action' => $this->nextAction($today?->day_number, $mistakesToReview),
        ];
    }

    private function streak(User $user): int
    {
        $dates = StudyLog::query()
            ->whereBelongsTo($user)
            ->where(fn ($query) => $query->where('minutes_spent', '>', 0)->orWhere('completed_questions', '>', 0))
            ->orderByDesc('log_date')
            ->pluck('log_date')
            ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $cursor = CarbonImmutable::today();

        if ($dates->first() !== $cursor->toDateString()) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;

        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function weeklyActivity(User $user): array
    {
        $dates = collect(range(6, 0))
            ->map(fn (int $daysAgo): CarbonImmutable => CarbonImmutable::today()->subDays($daysAgo))
            ->values();

        $logsByDate = StudyLog::query()
            ->whereBelongsTo($user)
            ->whereDate('log_date', '>=', $dates->first()->toDateString())
            ->whereDate('log_date', '<=', $dates->last()->toDateString())
            ->get()
            ->groupBy(fn (StudyLog $log): string => CarbonImmutable::parse($log->log_date)->toDateString());

        return $dates
            ->map(function (CarbonImmutable $date) use ($logsByDate): array {
                $logs = $logsByDate->get($date->toDateString(), collect());
                $accuracy = (int) round((float) $logs->avg('accuracy'));
                $minutes = (int) $logs->sum('minutes_spent');
                $questions = (int) $logs->sum('completed_questions');

                return [
                    'date' => $date->format('D'),
                    'iso_date' => $date->toDateString(),
                    'accuracy' => $accuracy,
                    'minutes' => $minutes,
                    'questions' => $questions,
                    'lessons' => (int) $logs->sum('completed_lessons'),
                    'intensity' => max($minutes, (int) round($accuracy / 4), $questions),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function pathMetrics(User $user, StudyPath $path, int $completedDays): array
    {
        $firstLogDate = StudyLog::query()
            ->whereBelongsTo($user)
            ->where(fn ($query) => $query->where('minutes_spent', '>', 0)->orWhere('completed_questions', '>', 0))
            ->orderBy('log_date')
            ->value('log_date');
        $startedAt = $firstLogDate ? CarbonImmutable::parse($firstLogDate) : null;
        $expectedDays = $startedAt
            ? min($path->duration_days, $startedAt->diffInDays(CarbonImmutable::today()) + 1)
            : 0;
        $pacingDelta = $completedDays - $expectedDays;

        return [
            'title' => $path->title,
            'duration_days' => $path->duration_days,
            'completed_days' => $completedDays,
            'completion_percentage' => $path->duration_days > 0
                ? (int) round(($completedDays / $path->duration_days) * 100)
                : 0,
            'days_remaining' => max(0, $path->duration_days - $completedDays),
            'pacing_delta' => $pacingDelta,
            'pacing_label' => $this->pacingLabel($completedDays, $pacingDelta),
            'started_at' => $startedAt?->toDateString(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $weeklyActivity
     * @return array<string, int>
     */
    private function studyLoad(array $weeklyActivity, UserProfile $profile): array
    {
        $activity = collect($weeklyActivity);
        $weeklyMinutes = (int) $activity->sum('minutes');
        $weeklyGoalMinutes = $profile->daily_goal_minutes * 7;
        $activeDays = $activity
            ->filter(fn (array $day): bool => $day['minutes'] > 0 || $day['questions'] > 0 || $day['lessons'] > 0)
            ->count();
        $accuracyDays = $activity->filter(fn (array $day): bool => $day['accuracy'] > 0);

        return [
            'weekly_minutes' => $weeklyMinutes,
            'weekly_goal_minutes' => $weeklyGoalMinutes,
            'goal_completion' => $weeklyGoalMinutes > 0
                ? min(100, (int) round(($weeklyMinutes / $weeklyGoalMinutes) * 100))
                : 0,
            'active_days' => $activeDays,
            'weekly_questions' => (int) $activity->sum('questions'),
            'average_accuracy' => (int) round((float) $accuracyDays->avg('accuracy')),
            'minutes_today' => (int) ($activity->last()['minutes'] ?? 0),
        ];
    }

    /**
     * @param  array<string, int>  $skillProgress
     * @return array<int, array<string, mixed>>
     */
    private function skillDiagnostics(User $user, array $skillProgress): array
    {
        return collect([SkillType::Listening, SkillType::Structure, SkillType::Reading])
            ->map(function (SkillType $skill) use ($user, $skillProgress): array {
                $scores = PracticeSession::query()
                    ->whereBelongsTo($user)
                    ->where('section_type', $skill)
                    ->whereNotNull('finished_at')
                    ->orderByDesc('finished_at')
                    ->limit(4)
                    ->pluck('score')
                    ->map(fn ($score): int => (int) round((float) $score));
                $latestScore = $scores->first();
                $previousAverage = $scores->skip(1)->avg();
                $score = $skillProgress[$skill->value] ?? 0;

                return [
                    'skill' => $skill->value,
                    'label' => $skill->label(),
                    'score' => $score,
                    'attempts' => PracticeSession::query()
                        ->whereBelongsTo($user)
                        ->where('section_type', $skill)
                        ->whereNotNull('finished_at')
                        ->count(),
                    'last_score' => $latestScore,
                    'momentum' => $latestScore && $previousAverage
                        ? (int) round($latestScore - $previousAverage)
                        : 0,
                    'status' => $this->skillStatus($score),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mistakeHeatmap(User $user): array
    {
        $counts = MistakeJournal::query()
            ->whereBelongsTo($user)
            ->whereIn('review_status', [ReviewStatus::New, ReviewStatus::Reviewing])
            ->get(['section_type'])
            ->groupBy(fn (MistakeJournal $mistake): string => $mistake->section_type->value)
            ->map(fn ($items): int => $items->count());

        return collect([SkillType::Listening, SkillType::Structure, SkillType::Reading, SkillType::Vocabulary, SkillType::Mixed])
            ->map(fn (SkillType $skill): array => [
                'skill' => $skill->value,
                'label' => $skill->label(),
                'count' => $counts->get($skill->value, 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function vocabularyStats(User $user): array
    {
        $counts = UserVocabulary::query()
            ->whereBelongsTo($user)
            ->get(['status'])
            ->groupBy(fn (UserVocabulary $vocabulary): string => $vocabulary->status->value)
            ->map(fn ($items): int => $items->count());
        $total = (int) $counts->sum();
        $mastered = $counts->get(VocabularyStatus::Mastered->value, 0);

        return [
            'total' => $total,
            'learning' => $counts->get(VocabularyStatus::Learning->value, 0),
            'mastered' => $mastered,
            'weak' => $counts->get(VocabularyStatus::Weak->value, 0),
            'review_later' => $counts->get(VocabularyStatus::ReviewLater->value, 0),
            'mastery_rate' => $total > 0 ? (int) round(($mastered / $total) * 100) : 0,
        ];
    }

    /**
     * @param  array<string, int>  $skillProgress
     * @param  array<string, int>  $vocabulary
     * @param  array<string, int>  $studyLoad
     * @return array<int, array<string, string>>
     */
    private function focusQueue(User $user, array $skillProgress, int $mistakesToReview, array $vocabulary, array $studyLoad): array
    {
        $weakestSkill = collect($skillProgress)->sort()->keys()->first() ?? SkillType::Structure->value;
        $weakestLabel = SkillType::tryFrom($weakestSkill)?->label() ?? 'Structure';
        $weeklyGoalSignal = $studyLoad['goal_completion'].'% weekly goal';

        return [
            [
                'kind' => 'practice',
                'title' => $weakestLabel.' repair sprint',
                'signal' => ($skillProgress[$weakestSkill] ?? 0).'% readiness',
                'description' => 'Prioritize the lowest section before adding new material.',
                'action' => 'Practice',
                'tone' => 'indigo',
            ],
            [
                'kind' => $mistakesToReview > 0 ? 'mistakes' : 'lesson',
                'title' => $mistakesToReview > 0 ? 'Error pattern review' : 'Daily lesson lock-in',
                'signal' => $mistakesToReview > 0 ? $mistakesToReview.' open mistakes' : $this->streak($user).' day streak',
                'description' => $mistakesToReview > 0
                    ? 'Convert wrong answers into reusable grammar and reading rules.'
                    : 'Keep the path moving with the current study day.',
                'action' => $mistakesToReview > 0 ? 'Review' : 'Start',
                'tone' => $mistakesToReview > 0 ? 'rose' : 'emerald',
            ],
            [
                'kind' => 'vocabulary',
                'title' => 'Vocabulary retention',
                'signal' => $vocabulary['weak'].' weak words',
                'description' => 'Stabilize weak words before they leak into reading accuracy.',
                'action' => 'Drill',
                'tone' => 'amber',
            ],
            [
                'kind' => 'lesson',
                'title' => 'Weekly load calibration',
                'signal' => $weeklyGoalSignal,
                'description' => 'Balance minutes, questions, and accuracy for sustainable momentum.',
                'action' => 'Resume',
                'tone' => 'cyan',
            ],
        ];
    }

    /**
     * @param  array<int>  $completedDayIds
     * @return array<int, array<string, mixed>>
     */
    private function upcomingDays(StudyPath $path, int $currentDayNumber, array $completedDayIds): array
    {
        return $path->studyDays
            ->filter(fn ($day): bool => $day->day_number >= $currentDayNumber)
            ->take(4)
            ->map(fn ($day): array => $this->studyPath->dayPayload($day, in_array($day->id, $completedDayIds, true)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentSessions(User $user): array
    {
        return PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereNotNull('finished_at')
            ->with('studyDay')
            ->orderByDesc('finished_at')
            ->limit(3)
            ->get()
            ->map(fn (PracticeSession $session): array => [
                'id' => $session->id,
                'section' => $session->section_type->label(),
                'score' => (int) round((float) $session->score),
                'questions' => $session->total_questions,
                'duration_minutes' => (int) ceil($session->duration_seconds / 60),
                'mode' => $session->mode->value,
                'day' => $session->studyDay ? 'Day '.$session->studyDay->day_number : 'Practice',
                'finished_at' => $session->finished_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    private function readinessTrend(User $user): int
    {
        $scores = PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->limit(6)
            ->pluck('score')
            ->map(fn ($score): int => (int) round((float) $score));
        $latestAverage = $scores->take(3)->avg();
        $previousAverage = $scores->skip(3)->take(3)->avg();

        if (! $latestAverage || ! $previousAverage) {
            return 0;
        }

        return (int) round($latestAverage - $previousAverage);
    }

    private function pacingLabel(int $completedDays, int $pacingDelta): string
    {
        if ($completedDays === 0) {
            return 'Ready to start';
        }

        if ($pacingDelta > 1) {
            return 'Ahead by '.$pacingDelta.' days';
        }

        if ($pacingDelta < -1) {
            return 'Behind by '.abs($pacingDelta).' days';
        }

        return 'On pace';
    }

    private function trendLabel(int $trend): string
    {
        return match (true) {
            $trend > 0 => '+'.$trend.' pts vs recent baseline',
            $trend < 0 => $trend.' pts vs recent baseline',
            default => 'Baseline forming',
        };
    }

    private function skillStatus(int $score): string
    {
        return match (true) {
            $score >= 82 => 'Strong',
            $score >= 68 => 'Stable',
            $score >= 50 => 'Needs reps',
            default => 'Critical',
        };
    }

    private function estimatedToeflScore(int $score): string
    {
        return match (true) {
            $score >= 90 => '620-677',
            $score >= 75 => '560-619',
            $score >= 60 => '500-559',
            $score >= 40 => '430-499',
            default => '310-429',
        };
    }

    private function estimatedToeflMidpoint(int $score): int
    {
        return match (true) {
            $score >= 90 => 648,
            $score >= 75 => 590,
            $score >= 60 => 530,
            $score >= 40 => 465,
            default => 370,
        };
    }

    private function nextAction(?int $dayNumber, int $mistakesToReview): string
    {
        if ($mistakesToReview > 0) {
            return 'Review mistakes';
        }

        return $dayNumber ? 'Start Day '.$dayNumber.' lesson' : 'Start focused practice';
    }
}
