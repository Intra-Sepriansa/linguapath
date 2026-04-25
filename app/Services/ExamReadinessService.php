<?php

namespace App\Services;

use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;

class ExamReadinessService
{
    public const array TARGETS = [
        'listening' => 50,
        'structure' => 40,
        'reading' => 50,
    ];

    public const array PASSAGE_READY_STATUSES = [
        'ready',
        'published',
        'reviewed',
    ];

    /**
     * @return Builder<Question>
     */
    public function examReadyQuestionQuery(SkillType $section): Builder
    {
        return $this->eligibleQuery($section);
    }

    /**
     * @return Builder<Question>
     */
    public function eligibleQuery(SkillType $section): Builder
    {
        $query = $this->activeExamQuestionQuery($section);

        $this->applyReadyMetadataFilter($query);
        $this->applyValidOptionsFilter($query);

        if ($section === SkillType::Reading) {
            $query
                ->where(fn (Builder $query): Builder => $query
                    ->whereNotNull('evidence_sentence')
                    ->where('evidence_sentence', '!=', ''))
                ->whereHas('passage', fn (Builder $passageQuery): Builder => $passageQuery
                    ->whereBetween('word_count', [300, 700])
                    ->whereIn('status', self::PASSAGE_READY_STATUSES));
        }

        if ($section === SkillType::Listening) {
            $query->whereHas('audioAsset', fn (Builder $audioQuery): Builder => $this->applyExamReadyAudioFilter($audioQuery));
        }

        return $query;
    }

    public function isListeningExamReady(Question $question): bool
    {
        return $this->isQuestionExamReadyForSection($question, SkillType::Listening);
    }

    public function isStructureExamReady(Question $question): bool
    {
        return $this->isQuestionExamReadyForSection($question, SkillType::Structure);
    }

    public function isReadingExamReady(Question $question): bool
    {
        return $this->isQuestionExamReadyForSection($question, SkillType::Reading);
    }

    /**
     * @return Builder<AudioAsset>
     */
    public function examReadyAudioQuery(): Builder
    {
        return $this->applyExamReadyAudioFilter(AudioAsset::query());
    }

    /**
     * @return Builder<Question>
     */
    public function listeningAudioBlockedQuestionQuery(): Builder
    {
        return $this->activeExamQuestionQuery(SkillType::Listening)
            ->whereDoesntHave('audioAsset', fn (Builder $query): Builder => $this->applyExamReadyAudioFilter($query));
    }

    /**
     * @return array<string, array{label: string, ready_count: int, target: int, percent: int, ready: bool, href: string, issue_count: int}>
     */
    public function sectionReadiness(): array
    {
        return $this->buildSectionReadiness($this->issueCounts());
    }

    /**
     * @return array<string, array{label: string, count: int, href: string}>
     */
    public function issueSummary(): array
    {
        return $this->buildIssueSummary($this->issueCounts());
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(): array
    {
        $issueCounts = $this->issueCounts();
        $sections = $this->buildSectionReadiness($issueCounts);
        $blockedSections = collect($sections)
            ->filter(fn (array $section): bool => ! $section['ready'])
            ->values()
            ->all();
        $totalCappedReady = collect($sections)->sum('capped_ready_count');

        return [
            'sections' => $sections,
            'full_exam_ready' => $blockedSections === [],
            'total_ready' => $totalCappedReady,
            'total_capped_ready' => $totalCappedReady,
            'total_raw_ready' => collect($sections)->sum('raw_ready_count'),
            'total_target' => array_sum(self::TARGETS),
            'blocked_sections' => $blockedSections,
            'primary_blocker_message' => $this->primaryBlockerMessage($blockedSections),
            'issues' => $this->buildIssueSummary($issueCounts),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function issueCounts(): array
    {
        return [
            'listening_missing_audio' => (clone $this->activeExamQuestionQuery(SkillType::Listening))
                ->whereNull('audio_asset_id')
                ->count(),
            'audio_not_real' => (clone $this->activeExamQuestionQuery(SkillType::Listening))
                ->whereHas('audioAsset', fn (Builder $query): Builder => $query->where('is_real_audio', false))
                ->count(),
            'transcript_not_reviewed' => (clone $this->activeExamQuestionQuery(SkillType::Listening))
                ->whereHas('audioAsset', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNull('transcript')
                        ->orWhere('transcript', '')
                        ->orWhereNull('transcript_reviewed_at')))
                ->count(),
            'audio_not_approved' => (clone $this->activeExamQuestionQuery(SkillType::Listening))
                ->whereHas('audioAsset', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNull('approved_at')
                        ->orWhereNotIn('status', AudioAsset::EXAM_READY_STATUSES)))
                ->count(),
            'reading_missing_evidence' => (clone $this->activeExamQuestionQuery(SkillType::Reading))
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('evidence_sentence')
                    ->orWhere('evidence_sentence', ''))
                ->count(),
            'invalid_options' => Question::query()
                ->where('exam_eligible', true)
                ->whereIn('status', Question::ACTIVE_STATUSES)
                ->where(fn (Builder $query): Builder => $query
                    ->whereRaw('(select count(*) from question_options where question_options.question_id = questions.id) != 4')
                    ->orWhereRaw('(select count(*) from question_options where question_options.question_id = questions.id and question_options.is_correct = 1) != 1'))
                ->count(),
        ];
    }

    /**
     * @param  array<string, int>  $issueCounts
     * @return array<string, array{label: string, ready_count: int, raw_ready_count: int, capped_ready_count: int, target: int, percent: int, ready: bool, href: string, issue_count: int}>
     */
    private function buildSectionReadiness(array $issueCounts): array
    {
        $sections = [];

        foreach (self::TARGETS as $section => $target) {
            $readyCount = $this->examReadyQuestionQuery(SkillType::from($section))->count();
            $cappedReadyCount = min($readyCount, $target);

            $sections[$section] = [
                'label' => SkillType::from($section)->label(),
                'ready_count' => $readyCount,
                'raw_ready_count' => $readyCount,
                'capped_ready_count' => $cappedReadyCount,
                'target' => $target,
                'percent' => min(100, (int) round(($cappedReadyCount / max($target, 1)) * 100)),
                'ready' => $readyCount >= $target,
                'href' => $this->sectionHref($section, $readyCount >= $target),
                'issue_count' => $this->sectionIssueCount($section, $issueCounts, $readyCount),
            ];
        }

        return $sections;
    }

    /**
     * @param  array<string, int>  $issueCounts
     * @return array<string, array{label: string, count: int, href: string}>
     */
    private function buildIssueSummary(array $issueCounts): array
    {
        return [
            'listening_missing_audio' => [
                'label' => 'Listening missing audio',
                'count' => $issueCounts['listening_missing_audio'],
                'href' => '/admin/questions?section_type=listening&quality=missing_audio',
            ],
            'audio_not_real' => [
                'label' => 'Audio not real',
                'count' => $issueCounts['audio_not_real'],
                'href' => '/admin/audio-assets',
            ],
            'transcript_not_reviewed' => [
                'label' => 'Transcript not reviewed',
                'count' => $issueCounts['transcript_not_reviewed'],
                'href' => '/admin/audio-assets',
            ],
            'audio_not_approved' => [
                'label' => 'Audio not approved',
                'count' => $issueCounts['audio_not_approved'],
                'href' => '/admin/audio-assets',
            ],
            'reading_missing_evidence' => [
                'label' => 'Reading missing evidence',
                'count' => $issueCounts['reading_missing_evidence'],
                'href' => '/admin/questions?section_type=reading&quality=missing_evidence',
            ],
            'invalid_options' => [
                'label' => 'Invalid options',
                'count' => $issueCounts['invalid_options'],
                'href' => '/admin/questions?quality=invalid_options',
            ],
        ];
    }

    /**
     * @return Builder<Question>
     */
    private function activeExamQuestionQuery(SkillType $section): Builder
    {
        return Question::query()
            ->where('exam_eligible', true)
            ->whereIn('status', Question::ACTIVE_STATUSES)
            ->where('section_type', $section);
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    private function applyReadyMetadataFilter(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('explanation')
                ->where('explanation', '!=', ''))
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('difficulty')
                ->where('difficulty', '!=', ''))
            ->whereNotNull('question_type')
            ->whereNotNull('skill_tag_id');
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    private function applyValidOptionsFilter(Builder $query): Builder
    {
        return $query
            ->whereRaw('(select count(*) from question_options where question_options.question_id = questions.id) = 4')
            ->whereRaw('(select count(*) from question_options where question_options.question_id = questions.id and question_options.is_correct = 1) = 1');
    }

    /**
     * @param  Builder<AudioAsset>  $query
     * @return Builder<AudioAsset>
     */
    private function applyExamReadyAudioFilter(Builder $query): Builder
    {
        return $query
            ->where('is_real_audio', true)
            ->whereIn('status', AudioAsset::EXAM_READY_STATUSES)
            ->whereNotNull('approved_at')
            ->whereNotNull('transcript_reviewed_at')
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('transcript')
                ->where('transcript', '!=', ''))
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('file_path')
                ->orWhereNotNull('audio_url'));
    }

    private function sectionHref(string $section, bool $ready): string
    {
        return match ($section) {
            'listening' => $ready
                ? '/admin/questions?section_type=listening'
                : '/admin/questions?section_type=listening&quality=missing_audio',
            'reading' => $ready
                ? '/admin/questions?section_type=reading'
                : '/admin/questions?section_type=reading&quality=missing_evidence',
            default => '/admin/questions?section_type='.$section,
        };
    }

    /**
     * @param  array<string, int>  $issueCounts
     */
    private function sectionIssueCount(string $section, array $issueCounts, int $readyCount): int
    {
        return match ($section) {
            'listening' => max(0, $this->activeExamQuestionQuery(SkillType::Listening)->count() - $readyCount),
            'reading' => max(0, $this->activeExamQuestionQuery(SkillType::Reading)->count() - $readyCount),
            default => max(0, $this->activeExamQuestionQuery(SkillType::from($section))->count() - $readyCount),
        };
    }

    /**
     * @param  list<array{label: string, raw_ready_count: int, target: int}>  $blockedSections
     */
    private function primaryBlockerMessage(array $blockedSections): ?string
    {
        if ($blockedSections === []) {
            return null;
        }

        $section = $blockedSections[0];

        return "Blocked: {$section['label']} needs {$section['target']} exam-ready questions, currently {$section['raw_ready_count']}.";
    }

    private function isQuestionExamReadyForSection(Question $question, SkillType $section): bool
    {
        if ($question->section_type !== $section) {
            return false;
        }

        return $this->examReadyQuestionQuery($section)
            ->whereKey($question->getKey())
            ->exists();
    }
}
