<?php

namespace App\Services;

use App\Enums\MistakeType;
use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Enums\VocabularyStatus;
use App\Models\MistakeJournal;
use App\Models\PracticeSession;
use App\Models\StudyLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserVocabulary;
use Carbon\CarbonImmutable;

class AnalyticsService
{
    public function __construct(private readonly ReadinessService $readiness) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $readinessScore = $this->readiness->score($user);
        $sectionAverages = $this->readiness->sectionAverages($user);
        $profile = UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $activity = $this->activitySeries($user);
        $skillBreakdown = $this->skillBreakdown($user, $sectionAverages);
        $mistakeTypes = $this->mistakeTypes($user);
        $summary = $this->summary($activity);
        $readinessTrend = $this->readinessTrend($user);
        $vocabulary = $this->vocabulary($user);

        return [
            'readiness' => [
                'score' => $readinessScore,
                'level' => $this->readiness->level($readinessScore),
                'trend' => $readinessTrend,
                'trend_label' => $this->trendLabel($readinessTrend),
            ],
            'summary' => $summary,
            'projection' => [
                'estimated_toefl' => $this->estimatedToeflScore($readinessScore),
                'target_score' => $profile->target_score,
                'target_gap' => max(0, $profile->target_score - $this->estimatedToeflMidpoint($readinessScore)),
                'daily_goal_minutes' => $profile->daily_goal_minutes,
                'exam_date' => $profile->exam_date?->toDateString(),
            ],
            'activity' => $activity,
            'weekly_accuracy' => $this->weeklyAccuracy($activity),
            'skill_breakdown' => $skillBreakdown,
            'mistake_types' => $mistakeTypes,
            'mistake_sections' => $this->mistakeSections($user),
            'study_minutes' => $this->studyMinutes($activity),
            'vocabulary' => $vocabulary,
            'recommendations' => $this->recommendations($skillBreakdown, $summary, $mistakeTypes, $vocabulary, $readinessScore),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activitySeries(User $user, int $days = 30): array
    {
        $dates = collect(range($days - 1, 0))
            ->map(fn (int $daysAgo): CarbonImmutable => CarbonImmutable::today()->subDays($daysAgo))
            ->values();

        $logsByDate = StudyLog::query()
            ->whereBelongsTo($user)
            ->whereDate('log_date', '>=', $dates->first()->toDateString())
            ->whereDate('log_date', '<=', $dates->last()->toDateString())
            ->get()
            ->groupBy(fn (StudyLog $log): string => CarbonImmutable::parse($log->log_date)->toDateString());

        $sessionsByDate = PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', '>=', $dates->first()->toDateString())
            ->whereDate('finished_at', '<=', $dates->last()->toDateString())
            ->get(['finished_at', 'score'])
            ->groupBy(fn (PracticeSession $session): string => CarbonImmutable::parse($session->finished_at)->toDateString());

        $activity = $dates
            ->map(function (CarbonImmutable $date) use ($logsByDate, $sessionsByDate): array {
                $logs = $logsByDate->get($date->toDateString(), collect());
                $sessions = $sessionsByDate->get($date->toDateString(), collect());
                $logAccuracy = (int) round((float) $logs->avg('accuracy'));
                $sessionAccuracy = (int) round((float) $sessions->avg('score'));
                $minutes = (int) $logs->sum('minutes_spent');
                $questions = (int) $logs->sum('completed_questions');

                return [
                    'date' => $date->format('M j'),
                    'day' => $date->format('D'),
                    'iso_date' => $date->toDateString(),
                    'accuracy' => $logAccuracy > 0 ? $logAccuracy : $sessionAccuracy,
                    'minutes' => $minutes,
                    'questions' => $questions,
                    'lessons' => (int) $logs->sum('completed_lessons'),
                    'sessions' => $sessions->count(),
                    'raw_intensity' => max($minutes, $questions * 2, $logAccuracy, $sessionAccuracy, $sessions->count() * 20),
                ];
            })
            ->values();

        $maxIntensity = max((int) $activity->max('raw_intensity'), 1);

        return $activity
            ->map(function (array $day) use ($maxIntensity): array {
                $rawIntensity = (int) $day['raw_intensity'];
                unset($day['raw_intensity']);

                return [
                    ...$day,
                    'intensity' => min(100, (int) round(($rawIntensity / $maxIntensity) * 100)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function weeklyAccuracy(array $activity): array
    {
        return collect($activity)
            ->take(-7)
            ->map(fn (array $day): array => [
                'date' => $day['day'],
                'accuracy' => $day['accuracy'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function studyMinutes(array $activity): array
    {
        return collect($activity)
            ->take(-7)
            ->map(fn (array $day): array => [
                'date' => $day['day'],
                'minutes' => $day['minutes'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $activity
     * @return array<string, mixed>
     */
    private function summary(array $activity): array
    {
        $days = collect($activity);
        $activeDays = $days->filter(fn (array $day): bool => $day['minutes'] > 0 || $day['questions'] > 0 || $day['lessons'] > 0 || $day['sessions'] > 0);
        $accuracyDays = $days->filter(fn (array $day): bool => $day['accuracy'] > 0);
        $currentWeek = $days->take(-7);
        $previousWeek = $days->slice(max(0, $days->count() - 14), 7);
        $weeklyAccuracy = (int) round((float) $currentWeek->filter(fn (array $day): bool => $day['accuracy'] > 0)->avg('accuracy'));
        $previousAccuracy = (int) round((float) $previousWeek->filter(fn (array $day): bool => $day['accuracy'] > 0)->avg('accuracy'));
        $totalMinutes = (int) $days->sum('minutes');
        $totalQuestions = (int) $days->sum('questions');
        $bestDay = $activeDays->sortByDesc('intensity')->first();

        return [
            'active_days' => $activeDays->count(),
            'total_minutes' => $totalMinutes,
            'total_questions' => $totalQuestions,
            'average_accuracy' => (int) round((float) $accuracyDays->avg('accuracy')),
            'weekly_minutes' => (int) $currentWeek->sum('minutes'),
            'weekly_questions' => (int) $currentWeek->sum('questions'),
            'weekly_accuracy' => $weeklyAccuracy,
            'weekly_minutes_trend' => (int) $currentWeek->sum('minutes') - (int) $previousWeek->sum('minutes'),
            'weekly_accuracy_trend' => $weeklyAccuracy - $previousAccuracy,
            'consistency_score' => $days->isNotEmpty()
                ? (int) round(($activeDays->count() / $days->count()) * 100)
                : 0,
            'study_efficiency' => $totalMinutes > 0
                ? (int) round(($totalQuestions / $totalMinutes) * 30)
                : 0,
            'best_day' => $bestDay ? [
                'label' => CarbonImmutable::parse($bestDay['iso_date'])->format('D, M j'),
                'minutes' => $bestDay['minutes'],
                'accuracy' => $bestDay['accuracy'],
                'questions' => $bestDay['questions'],
            ] : null,
        ];
    }

    /**
     * @param  array<string, int>  $sectionAverages
     * @return array<int, array<string, mixed>>
     */
    private function skillBreakdown(User $user, array $sectionAverages): array
    {
        $trackedSkills = collect([SkillType::Listening, SkillType::Structure, SkillType::Reading]);
        $sessionsBySkill = PracticeSession::query()
            ->whereBelongsTo($user)
            ->whereNotNull('finished_at')
            ->whereIn('section_type', $trackedSkills->map->value->all())
            ->orderByDesc('finished_at')
            ->get(['section_type', 'score', 'total_questions', 'duration_seconds', 'finished_at'])
            ->groupBy(fn (PracticeSession $session): string => $session->section_type->value);
        $mistakesBySkill = MistakeJournal::query()
            ->whereBelongsTo($user)
            ->get(['section_type'])
            ->groupBy(fn (MistakeJournal $mistake): string => $mistake->section_type->value)
            ->map(fn ($items): int => $items->count());

        return $trackedSkills
            ->map(function (SkillType $skill) use ($sectionAverages, $sessionsBySkill, $mistakesBySkill): array {
                $sessions = $sessionsBySkill->get($skill->value, collect());
                $recentScores = $sessions
                    ->take(6)
                    ->pluck('score')
                    ->map(fn ($score): int => (int) round((float) $score));
                $latestScore = $recentScores->first();
                $previousAverage = $recentScores->skip(1)->avg();
                $questions = (int) $sessions->sum('total_questions');
                $mistakes = $mistakesBySkill->get($skill->value, 0);
                $score = $sectionAverages[$skill->value] ?? 0;

                return [
                    'key' => $skill->value,
                    'skill' => $skill->label(),
                    'score' => $score,
                    'attempts' => $sessions->count(),
                    'latest_score' => $latestScore,
                    'momentum' => $latestScore && $previousAverage
                        ? (int) round($latestScore - $previousAverage)
                        : 0,
                    'questions' => $questions,
                    'minutes' => (int) ceil($sessions->sum('duration_seconds') / 60),
                    'mistakes' => $mistakes,
                    'mistake_rate' => $questions > 0
                        ? (int) round(($mistakes / $questions) * 100)
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
    private function mistakeTypes(User $user): array
    {
        $counts = MistakeJournal::query()
            ->whereBelongsTo($user)
            ->get(['mistake_type'])
            ->groupBy(fn (MistakeJournal $mistake): string => $mistake->mistake_type->value)
            ->map(fn ($items): int => $items->count());
        $total = max((int) $counts->sum(), 1);

        return collect(MistakeType::cases())
            ->map(fn (MistakeType $type): array => [
                'name' => ucfirst($type->value),
                'key' => $type->value,
                'value' => $counts->get($type->value, 0),
                'percentage' => (int) round(($counts->get($type->value, 0) / $total) * 100),
                'tone' => $this->mistakeTone($type),
            ])
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mistakeSections(User $user): array
    {
        $counts = MistakeJournal::query()
            ->whereBelongsTo($user)
            ->whereIn('review_status', [ReviewStatus::New, ReviewStatus::Reviewing])
            ->get(['section_type'])
            ->groupBy(fn (MistakeJournal $mistake): string => $mistake->section_type->value)
            ->map(fn ($items): int => $items->count());

        return collect([SkillType::Listening, SkillType::Structure, SkillType::Reading, SkillType::Vocabulary, SkillType::Mixed])
            ->map(fn (SkillType $skill): array => [
                'key' => $skill->value,
                'section' => $skill->label(),
                'open' => $counts->get($skill->value, 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function vocabulary(User $user): array
    {
        $items = UserVocabulary::query()
            ->whereBelongsTo($user)
            ->get(['status', 'review_count', 'last_reviewed_at']);
        $counts = $items
            ->groupBy(fn (UserVocabulary $vocabulary): string => $vocabulary->status->value)
            ->map(fn ($items): int => $items->count());
        $total = $items->count();
        $mastered = $counts->get(VocabularyStatus::Mastered->value, 0);
        $reviewReady = $counts->get(VocabularyStatus::Weak->value, 0) + $counts->get(VocabularyStatus::ReviewLater->value, 0);

        return [
            'total' => $total,
            'learning' => $counts->get(VocabularyStatus::Learning->value, 0),
            'mastered' => $mastered,
            'weak' => $counts->get(VocabularyStatus::Weak->value, 0),
            'review_later' => $counts->get(VocabularyStatus::ReviewLater->value, 0),
            'review_ready' => $reviewReady,
            'mastery_rate' => $total > 0 ? (int) round(($mastered / $total) * 100) : 0,
            'retention_rate' => $total > 0 ? (int) round((($mastered + $counts->get(VocabularyStatus::ReviewLater->value, 0)) / $total) * 100) : 0,
            'reviewed_this_week' => $items
                ->filter(fn (UserVocabulary $vocabulary): bool => $vocabulary->last_reviewed_at?->gte(now()->subDays(7)) ?? false)
                ->count(),
            'average_reviews' => (int) round((float) $items->avg('review_count')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $skillBreakdown
     * @param  array<string, mixed>  $summary
     * @param  array<int, array<string, mixed>>  $mistakeTypes
     * @param  array<string, int>  $vocabulary
     * @return array<int, array<string, string>>
     */
    private function recommendations(array $skillBreakdown, array $summary, array $mistakeTypes, array $vocabulary, int $readinessScore): array
    {
        $weakestSkill = collect($skillBreakdown)->sortBy('score')->first();
        $largestMistake = collect($mistakeTypes)->sortByDesc('value')->first();

        return [
            [
                'kind' => 'practice',
                'title' => ($weakestSkill['skill'] ?? 'Structure').' recovery sprint',
                'signal' => ($weakestSkill['score'] ?? 0).'% readiness',
                'description' => 'Target the lowest scoring section before adding broad mixed practice.',
                'action' => 'Practice',
                'tone' => 'indigo',
                'priority' => ($weakestSkill['score'] ?? 0) < 60 ? 'High' : 'Medium',
            ],
            [
                'kind' => 'mistakes',
                'title' => $largestMistake ? $largestMistake['name'].' pattern repair' : 'Error pattern scan',
                'signal' => $largestMistake ? $largestMistake['value'].' logged errors' : 'No active pattern',
                'description' => $largestMistake
                    ? 'Convert repeated misses into a short rule, example, and retest loop.'
                    : 'Keep mistake review ready for the next completed practice set.',
                'action' => 'Review',
                'tone' => $largestMistake ? 'rose' : 'slate',
                'priority' => $largestMistake ? 'High' : 'Low',
            ],
            [
                'kind' => 'vocabulary',
                'title' => 'Retention lock',
                'signal' => $vocabulary['review_ready'].' words ready',
                'description' => 'Stabilize weak and deferred vocabulary before reading accuracy drops.',
                'action' => 'Drill',
                'tone' => 'amber',
                'priority' => $vocabulary['review_ready'] > 0 ? 'Medium' : 'Low',
            ],
            [
                'kind' => 'consistency',
                'title' => 'Load calibration',
                'signal' => $summary['consistency_score'].'% consistency',
                'description' => $readinessScore >= 75
                    ? 'Protect the current pace with shorter daily reviews and timed sections.'
                    : 'Raise active days first, then increase question volume.',
                'action' => 'Calibrate',
                'tone' => 'cyan',
                'priority' => $summary['consistency_score'] < 50 ? 'High' : 'Medium',
            ],
        ];
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

    private function trendLabel(int $trend): string
    {
        return match (true) {
            $trend > 0 => '+'.$trend.' pts vs baseline',
            $trend < 0 => $trend.' pts vs baseline',
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

    private function mistakeTone(MistakeType $type): string
    {
        return match ($type) {
            MistakeType::Grammar => 'rose',
            MistakeType::Vocabulary => 'amber',
            MistakeType::Listening => 'cyan',
            MistakeType::Reading => 'indigo',
            MistakeType::Time => 'slate',
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
}
