<?php

namespace App\Services;

use App\Enums\MistakeType;
use App\Enums\SkillType;
use App\Models\ExamAnswer;
use App\Models\ExamSection;
use App\Models\ExamSimulation;
use App\Models\MistakeJournal;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSimulationService
{
    public const SCORE_DISCLAIMER = 'Internal estimate, not an official ETS score.';

    public function __construct(private readonly ExamReadinessService $examReadiness) {}

    /**
     * @return array<string, array{label: string, count: int, duration_seconds: int, scaled_min: int, scaled_max: int, position: int}>
     */
    public function sectionSpecs(): array
    {
        return [
            SkillType::Listening->value => [
                'label' => 'Listening Comprehension',
                'count' => 50,
                'duration_seconds' => 35 * 60,
                'scaled_min' => 31,
                'scaled_max' => 68,
                'position' => 1,
            ],
            SkillType::Structure->value => [
                'label' => 'Structure & Written Expression',
                'count' => 40,
                'duration_seconds' => 25 * 60,
                'scaled_min' => 31,
                'scaled_max' => 68,
                'position' => 2,
            ],
            SkillType::Reading->value => [
                'label' => 'Reading Comprehension',
                'count' => 50,
                'duration_seconds' => 55 * 60,
                'scaled_min' => 31,
                'scaled_max' => 67,
                'position' => 3,
            ],
        ];
    }

    public function start(User $user): ExamSimulation
    {
        $questionSets = collect($this->sectionSpecs())
            ->mapWithKeys(fn (array $spec, string $section): array => [
                $section => $this->questionsForSection(SkillType::from($section), $spec['count']),
            ]);

        return DB::transaction(function () use ($user, $questionSets): ExamSimulation {
            $examStartedAt = now();
            $simulation = ExamSimulation::query()->create([
                'user_id' => $user->id,
                'status' => 'in_progress',
                'target_score' => $user->profile?->target_score,
                'total_questions' => $questionSets->sum(fn (Collection $questions): int => $questions->count()),
                'started_at' => $examStartedAt,
            ]);

            foreach ($this->sectionSpecs() as $section => $spec) {
                $sectionStartedAt = $spec['position'] === 1 ? $examStartedAt : null;
                $examSection = ExamSection::query()->create([
                    'exam_simulation_id' => $simulation->id,
                    'section_type' => $section,
                    'position' => $spec['position'],
                    'status' => $spec['position'] === 1 ? 'active' : 'locked',
                    'duration_seconds' => $spec['duration_seconds'],
                    'total_questions' => $spec['count'],
                    'started_at' => $sectionStartedAt,
                    'ends_at' => $sectionStartedAt?->copy()->addSeconds($spec['duration_seconds']),
                ]);

                foreach ($questionSets[$section] as $question) {
                    ExamAnswer::query()->create([
                        'exam_simulation_id' => $simulation->id,
                        'exam_section_id' => $examSection->id,
                        'question_id' => $question->id,
                    ]);
                }
            }

            return $simulation->load(['sections.answers.question.options']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user, ExamSimulation $simulation): array
    {
        $this->ensureOwner($user, $simulation);

        $simulation->load([
            'sections.answers.question.options',
            'sections.answers.question.passage',
            'sections.answers.question.audioAsset',
            'sections.answers.question.correctOption',
        ]);

        $review = $simulation->status === 'completed';
        $currentSection = $this->currentSection($simulation);
        $answers = $currentSection
            ? $currentSection->answers
            : $simulation->sections->flatMap(fn (ExamSection $section) => $section->answers);

        return [
            'id' => $simulation->id,
            'status' => $simulation->status,
            'server_now' => now()->toIso8601String(),
            'exam_started_at' => $simulation->started_at?->toIso8601String(),
            'started_at' => $simulation->started_at?->toIso8601String(),
            'finished_at' => $simulation->finished_at?->toIso8601String(),
            'total_questions' => $simulation->total_questions,
            'answered_count' => $simulation->answers()->whereNotNull('selected_option_id')->count(),
            'score_disclaimer' => self::SCORE_DISCLAIMER,
            'locked_sections' => $simulation->sections
                ->where('status', 'locked')
                ->pluck('section_type')
                ->map(fn (SkillType $skill): string => $skill->value)
                ->values()
                ->all(),
            'sections' => $simulation->sections->map(fn (ExamSection $section): array => $this->sectionPayload($section))->values()->all(),
            'current_section' => $currentSection ? $this->sectionPayload($currentSection) : null,
            'questions' => $answers
                ->values()
                ->map(fn (ExamAnswer $answer, int $index): array => $this->answerPayload($answer, $index + 1, $review))
                ->all(),
        ];
    }

    public function answer(User $user, ExamSimulation $simulation, int $answerId, int $optionId, int $seconds = 0): ExamAnswer
    {
        $this->ensureOwner($user, $simulation);

        if ($simulation->status !== 'in_progress') {
            throw ValidationException::withMessages(['exam' => 'This simulation is already finished.']);
        }

        $answer = ExamAnswer::query()
            ->whereBelongsTo($simulation)
            ->with(['examSection', 'question.options'])
            ->findOrFail($answerId);

        if ($answer->examSection->status !== 'active') {
            throw ValidationException::withMessages(['section' => 'This section is locked or already completed.']);
        }

        if ($this->remainingSeconds($answer->examSection) <= 0) {
            throw ValidationException::withMessages(['section' => 'This section time is over. Finish the section to continue.']);
        }

        $option = QuestionOption::query()
            ->where('question_id', $answer->question_id)
            ->findOrFail($optionId);

        $answer->update([
            'selected_option_id' => $option->id,
            'is_correct' => $option->is_correct,
            'time_spent_seconds' => $seconds,
            'answered_at' => now(),
        ]);

        return $answer->refresh();
    }

    public function finishSection(User $user, ExamSimulation $simulation): ExamSimulation
    {
        $this->ensureOwner($user, $simulation);

        if ($simulation->status !== 'in_progress') {
            return $simulation;
        }

        return DB::transaction(function () use ($simulation): ExamSimulation {
            $simulation->load(['sections.answers']);
            $section = $this->currentSection($simulation);

            if (! $section) {
                return $this->complete($simulation);
            }

            $this->scoreSection($section, $this->remainingSeconds($section) <= 0 ? 'timed_out' : 'manual');

            $nextSection = $simulation->sections
                ->where('position', $section->position + 1)
                ->first();

            if ($nextSection) {
                $nextStartedAt = now();
                $nextSection->update([
                    'status' => 'active',
                    'started_at' => $nextStartedAt,
                    'ends_at' => $nextStartedAt->copy()->addSeconds($nextSection->duration_seconds),
                ]);

                return $simulation->refresh();
            }

            return $this->complete($simulation);
        });
    }

    public function finish(User $user, ExamSimulation $simulation): ExamSimulation
    {
        $this->ensureOwner($user, $simulation);

        return DB::transaction(function () use ($simulation): ExamSimulation {
            $simulation->load(['sections.answers']);

            foreach ($simulation->sections as $section) {
                if ($section->status !== 'completed') {
                    $this->scoreSection($section, 'exam_finished');
                }
            }

            return $this->complete($simulation);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function resultPayload(User $user, ExamSimulation $simulation): array
    {
        $this->ensureOwner($user, $simulation);
        $simulation->load([
            'sections.answers.question.correctOption',
            'sections.answers.question.options',
            'sections.answers.question.passage',
            'sections.answers.question.audioAsset',
            'sections.answers.selectedOption',
        ]);

        return [
            'id' => $simulation->id,
            'status' => $simulation->status,
            'correct_answers' => $simulation->correct_answers,
            'total_questions' => $simulation->total_questions,
            'estimated_total_score' => $simulation->estimated_total_score,
            'score_label' => 'Estimated TOEFL ITP score',
            'score_disclaimer' => self::SCORE_DISCLAIMER,
            'finished_at' => $simulation->finished_at?->toIso8601String(),
            'sections' => $simulation->sections
                ->map(fn (ExamSection $section): array => [
                    ...$this->sectionPayload($section),
                    'raw_score' => $section->correct_answers.'/'.$section->total_questions,
                    'accuracy' => $section->total_questions > 0
                        ? (int) round(($section->correct_answers / $section->total_questions) * 100)
                        : 0,
                ])
                ->values()
                ->all(),
            'weaknesses' => $this->weaknesses($simulation),
            'recommendations' => $this->recommendations($simulation),
            'answers' => $simulation->sections
                ->flatMap(fn (ExamSection $section) => $section->answers)
                ->values()
                ->map(fn (ExamAnswer $answer, int $index): array => $this->answerPayload($answer, $index + 1, true))
                ->all(),
            'history' => $this->history($user),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(User $user): array
    {
        return ExamSimulation::query()
            ->whereBelongsTo($user)
            ->where('status', 'completed')
            ->latest('finished_at')
            ->limit(8)
            ->get()
            ->map(fn (ExamSimulation $simulation): array => [
                'id' => $simulation->id,
                'score' => $simulation->estimated_total_score,
                'correct_answers' => $simulation->correct_answers,
                'total_questions' => $simulation->total_questions,
                'finished_at' => $simulation->finished_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    private function complete(ExamSimulation $simulation): ExamSimulation
    {
        $simulation->load(['sections.answers.question.correctOption', 'sections.answers.selectedOption', 'sections.answers.question.skillTag', 'user']);

        $correct = (int) $simulation->sections->sum('correct_answers');
        $sectionScores = $simulation->sections
            ->pluck('estimated_scaled_score')
            ->filter()
            ->values();
        $estimatedTotal = $sectionScores->isNotEmpty()
            ? (int) round($sectionScores->avg() * 10)
            : null;

        $simulation->update([
            'status' => 'completed',
            'correct_answers' => $correct,
            'estimated_total_score' => $estimatedTotal,
            'finished_at' => now(),
        ]);

        $simulation->sections
            ->flatMap(fn (ExamSection $section) => $section->answers)
            ->where('is_correct', false)
            ->each(fn (ExamAnswer $answer) => $this->recordMistake($simulation->user, $answer));

        return $simulation->refresh();
    }

    private function scoreSection(ExamSection $section, string $reason = 'manual'): void
    {
        $section->load('answers');
        $correct = $section->answers->where('is_correct', true)->count();
        $submittedAt = now();

        $section->update([
            'status' => 'completed',
            'correct_answers' => $correct,
            'estimated_scaled_score' => $this->scaledScore($section->section_type, $correct, $section->total_questions),
            'finished_at' => $submittedAt,
            'submitted_at' => $submittedAt,
            'submission_reason' => $reason,
        ]);
    }

    private function scaledScore(SkillType $section, int $rawScore, int $total): int
    {
        $spec = $this->sectionSpecs()[$section->value];
        $range = $spec['scaled_max'] - $spec['scaled_min'];

        return min(
            $spec['scaled_max'],
            max($spec['scaled_min'], $spec['scaled_min'] + (int) round(($rawScore / max($total, 1)) * $range))
        );
    }

    /**
     * @return Collection<int, Question>
     */
    private function questionsForSection(SkillType $section, int $count): Collection
    {
        $query = $this->examReadiness
            ->examReadyQuestionQuery($section)
            ->with(['options', 'audioAsset', 'passage']);

        $questions = $query
            ->inRandomOrder()
            ->limit($count)
            ->get();

        if ($questions->count() < $count) {
            throw ValidationException::withMessages([
                'exam' => "Not enough {$section->label()} questions for a full TOEFL ITP simulation.",
            ]);
        }

        return $questions;
    }

    private function currentSection(ExamSimulation $simulation): ?ExamSection
    {
        return $simulation->sections
            ->sortBy('position')
            ->first(fn (ExamSection $section): bool => $section->status === 'active');
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionPayload(ExamSection $section): array
    {
        $section->loadMissing('answers');
        $spec = $this->sectionSpecs()[$section->section_type->value];

        return [
            'id' => $section->id,
            'section_type' => $section->section_type->value,
            'label' => $spec['label'],
            'position' => $section->position,
            'status' => $section->status,
            'duration_seconds' => $section->duration_seconds,
            'section_duration_seconds' => $section->duration_seconds,
            'remaining_seconds' => $this->remainingSeconds($section),
            'total_questions' => $section->total_questions,
            'answered_count' => $section->answers->whereNotNull('selected_option_id')->count(),
            'correct_answers' => $section->correct_answers,
            'estimated_scaled_score' => $section->estimated_scaled_score,
            'started_at' => $section->started_at?->toIso8601String(),
            'section_started_at' => $section->started_at?->toIso8601String(),
            'ends_at' => $section->ends_at?->toIso8601String(),
            'section_ends_at' => $section->ends_at?->toIso8601String(),
            'finished_at' => $section->finished_at?->toIso8601String(),
            'submitted_at' => $section->submitted_at?->toIso8601String(),
            'submission_reason' => $section->submission_reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function answerPayload(ExamAnswer $answer, int $position, bool $review): array
    {
        $question = $answer->question;
        $audio = $question->audioAsset;
        $passage = $question->passage;

        return [
            'id' => $question->id,
            'answer_id' => $answer->id,
            'position' => $position,
            'section_type' => $question->section_type->value,
            'question_type' => $question->question_type->value,
            'difficulty' => $question->difficulty,
            'question_text' => $question->question_text,
            'passage_text' => $passage?->body ?? $question->passage_text,
            'evidence_sentence' => $review ? $question->evidence_sentence : null,
            'audio_url' => $audio?->playbackUrl() ?? $question->audio_url,
            'audio_source_label' => $audio?->is_real_audio ? 'Uploaded audio' : 'Audio file not uploaded yet',
            'has_real_audio' => (bool) $audio?->is_real_audio,
            'playback_limit_exam' => $audio?->playback_limit_exam ?? 1,
            'audio_playback_text' => null,
            'transcript' => $review ? ($audio?->transcript ?? $question->transcript) : null,
            'selected_option_id' => $answer->selected_option_id,
            'is_answered' => $answer->selected_option_id !== null,
            'is_correct' => $review ? $answer->is_correct : null,
            'correct_option_id' => $review ? $question->correctOption?->id : null,
            'correct_option_text' => $review ? $question->correctOption?->option_text : null,
            'selected_option_text' => $review ? $answer->selectedOption?->option_text : null,
            'explanation' => $review ? $question->explanation : null,
            'why_correct' => $review ? ($question->why_correct ?? $question->explanation) : null,
            'why_wrong' => $review ? ($question->why_wrong ?? 'Review the key evidence and compare it with your selected option.') : null,
            'options' => $question->options->map(fn (QuestionOption $option): array => [
                'id' => $option->id,
                'label' => $option->option_label,
                'text' => $option->option_text,
            ])->values()->all(),
        ];
    }

    private function remainingSeconds(ExamSection $section): int
    {
        if ($section->status !== 'active' || ! $section->started_at) {
            return $section->status === 'completed' ? 0 : $section->duration_seconds;
        }

        $endsAt = $section->ends_at ?? $section->started_at->copy()->addSeconds($section->duration_seconds);

        return max(0, $endsAt->getTimestamp() - now()->getTimestamp());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function weaknesses(ExamSimulation $simulation): array
    {
        return $simulation->sections
            ->flatMap(fn (ExamSection $section): EloquentCollection => $section->answers)
            ->filter(fn (ExamAnswer $answer): bool => ! $answer->is_correct)
            ->groupBy(fn (ExamAnswer $answer): string => $answer->question->section_type->label().' / '.str_replace('_', ' ', $answer->question->question_type->value))
            ->map(fn ($items, string $label): array => [
                'label' => $label,
                'count' => $items->count(),
                'priority' => $items->count() >= 5 ? 'High' : 'Medium',
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recommendations(ExamSimulation $simulation): array
    {
        $weakestSection = $simulation->sections
            ->sortBy(fn (ExamSection $section): float => $section->correct_answers / max($section->total_questions, 1))
            ->first();
        $label = $weakestSection ? $this->sectionSpecs()[$weakestSection->section_type->value]['label'] : 'Structure';

        return [
            [
                'kind' => 'exam_review',
                'title' => 'Review every missed item',
                'description' => 'Read the explanation and write one reusable rule for each wrong answer.',
                'action' => 'Open mistakes',
                'priority' => 'High',
            ],
            [
                'kind' => 'section_repair',
                'title' => $label.' repair sprint',
                'description' => 'Run a focused drill on the weakest section before taking another full simulation.',
                'action' => 'Practice weakest section',
                'priority' => 'High',
            ],
            [
                'kind' => 'vocabulary',
                'title' => 'Vocabulary retention pass',
                'description' => 'Review due vocabulary before the next Reading section to reduce context mistakes.',
                'action' => 'Review vocabulary',
                'priority' => 'Medium',
            ],
        ];
    }

    private function recordMistake(User $user, ExamAnswer $answer): void
    {
        $answer->loadMissing(['question.correctOption', 'selectedOption', 'question.skillTag']);

        MistakeJournal::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'exam_answer_id' => $answer->id,
            ],
            [
                'question_id' => $answer->question_id,
                'skill_tag_id' => $answer->question->skill_tag_id,
                'section_type' => $answer->question->section_type,
                'mistake_type' => $this->mistakeTypeFor($answer->question->section_type),
                'user_answer' => $answer->selectedOption?->option_text,
                'correct_answer' => $answer->question->correctOption?->option_text,
                'note' => $answer->question->explanation,
                'why_wrong' => $answer->question->why_wrong,
                'why_correct' => $answer->question->why_correct ?? $answer->question->explanation,
                'review_status' => 'new',
                'next_review_at' => now()->addDay(),
            ]
        );
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

    private function ensureOwner(User $user, ExamSimulation $simulation): void
    {
        if ($simulation->user_id !== $user->id) {
            throw new AuthorizationException;
        }
    }
}
