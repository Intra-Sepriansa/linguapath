<?php

namespace App\Services;

use App\Enums\PracticeMode;
use App\Enums\SkillType;
use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudyDay;
use App\Models\StudyLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PracticeService
{
    public function __construct(private readonly MistakeJournalService $mistakes) {}

    /**
     * @param  array{section_type: string, mode: string, study_day_id?: int|null, question_count?: int|null}  $data
     */
    public function start(User $user, array $data): PracticeSession
    {
        $section = SkillType::from($data['section_type']);
        $mode = PracticeMode::from($data['mode']);
        $questionCount = min(max((int) ($data['question_count'] ?? 10), 1), 50);
        $studyDayId = $mode === PracticeMode::Lesson ? ($data['study_day_id'] ?? null) : null;

        $questions = $this->questionsForSession($section, $studyDayId, $questionCount);

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'section_type' => 'No questions are available for this practice.',
            ]);
        }

        return DB::transaction(function () use ($user, $studyDayId, $section, $mode, $questions): PracticeSession {
            $session = PracticeSession::query()->create([
                'user_id' => $user->id,
                'study_day_id' => $studyDayId,
                'section_type' => $section,
                'mode' => $mode,
                'total_questions' => $questions->count(),
                'started_at' => now(),
            ]);

            foreach ($questions as $question) {
                PracticeAnswer::query()->create([
                    'practice_session_id' => $session->id,
                    'question_id' => $question->id,
                ]);
            }

            return $session->load(['answers.question.options']);
        });
    }

    public function answer(User $user, PracticeSession $session, int $questionId, int $optionId, int $seconds = 0): PracticeAnswer
    {
        $this->ensureOwner($user, $session);

        if ($session->finished_at) {
            throw ValidationException::withMessages(['session' => 'This practice session is already finished.']);
        }

        $answer = $session->answers()->where('question_id', $questionId)->firstOrFail();

        if ($answer->selected_option_id) {
            return $answer;
        }

        $option = QuestionOption::query()
            ->where('question_id', $questionId)
            ->findOrFail($optionId);

        $answer->update([
            'selected_option_id' => $option->id,
            'is_correct' => $option->is_correct,
            'time_spent_seconds' => $seconds,
        ]);

        return $answer->refresh();
    }

    public function finish(User $user, PracticeSession $session): PracticeSession
    {
        $this->ensureOwner($user, $session);

        if ($session->finished_at) {
            return $session;
        }

        return DB::transaction(function () use ($user, $session): PracticeSession {
            $session->load(['answers.question.correctOption', 'answers.selectedOption']);

            if ($session->answers->contains(fn (PracticeAnswer $answer): bool => $answer->selected_option_id === null)) {
                throw ValidationException::withMessages([
                    'session' => 'Answer every question before finishing this mini-test.',
                ]);
            }

            $total = max($session->answers->count(), 1);
            $correct = $session->answers->where('is_correct', true)->count();
            $score = round(($correct / $total) * 100, 2);
            $duration = max($session->started_at?->diffInSeconds(now()) ?? 0, (int) $session->answers->sum('time_spent_seconds'));
            $passed = $correct === $total;

            $session->update([
                'correct_answers' => $correct,
                'score' => $score,
                'duration_seconds' => $duration,
                'finished_at' => now(),
            ]);

            $session->answers
                ->where('is_correct', false)
                ->each(fn (PracticeAnswer $answer) => $this->mistakes->recordForAnswer($user, $answer));

            if ($session->study_day_id) {
                StudyLog::query()->updateOrCreate(
                    ['user_id' => $user->id, 'study_day_id' => $session->study_day_id],
                    [
                        'minutes_spent' => max((int) ceil($duration / 60), 10),
                        'completed_lessons' => $passed ? 1 : 0,
                        'completed_questions' => $total,
                        'accuracy' => $score,
                        'log_date' => today(),
                    ]
                );
            }

            return $session->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(PracticeSession $session): array
    {
        $session->load(['studyDay', 'answers.question.correctOption', 'answers.question.options']);

        return [
            'id' => $session->id,
            'section_type' => $session->section_type->value,
            'mode' => $session->mode->value,
            'total_questions' => $session->total_questions,
            'answered_count' => $session->answers->whereNotNull('selected_option_id')->count(),
            'correct_count' => $session->answers->where('is_correct', true)->count(),
            'progress_percent' => $session->total_questions > 0
                ? (int) round(($session->answers->whereNotNull('selected_option_id')->count() / $session->total_questions) * 100)
                : 0,
            'finished_at' => $session->finished_at?->toIso8601String(),
            'study_day' => $session->studyDay ? [
                'id' => $session->studyDay->id,
                'day_number' => $session->studyDay->day_number,
                'title' => $session->studyDay->title,
            ] : null,
            'questions' => $session->answers->values()->map(fn (PracticeAnswer $answer, int $index): array => [
                'id' => $answer->question->id,
                'answer_id' => $answer->id,
                'position' => $index + 1,
                'section_type' => $answer->question->section_type->value,
                'question_type' => $answer->question->question_type->value,
                'difficulty' => $answer->question->difficulty,
                'question_text' => $answer->question->question_text,
                'passage_text' => $answer->question->passage_text,
                'transcript' => $answer->question->transcript,
                'selected_option_id' => $answer->selected_option_id,
                'is_answered' => $answer->selected_option_id !== null,
                'is_correct' => $answer->selected_option_id ? $answer->is_correct : null,
                'correct_option_id' => $answer->selected_option_id ? $answer->question->correctOption?->id : null,
                'correct_option_text' => $answer->selected_option_id ? $answer->question->correctOption?->option_text : null,
                'explanation' => $answer->selected_option_id ? $answer->question->explanation : null,
                'options' => $answer->question->options->map(fn (QuestionOption $option): array => [
                    'id' => $option->id,
                    'label' => $option->option_label,
                    'text' => $option->option_text,
                ])->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resultPayload(PracticeSession $session): array
    {
        $session->load(['studyDay', 'answers.question.correctOption', 'answers.selectedOption']);

        return [
            'id' => $session->id,
            'score' => (int) round($session->score),
            'correct_answers' => $session->correct_answers,
            'total_questions' => $session->total_questions,
            'wrong_answers' => max($session->total_questions - $session->correct_answers, 0),
            'duration_seconds' => $session->duration_seconds,
            'duration_minutes' => (int) ceil($session->duration_seconds / 60),
            'section_type' => $session->section_type->value,
            'mode' => $session->mode->value,
            'passed' => $session->total_questions > 0 && $session->correct_answers === $session->total_questions,
            'passing_score' => 100,
            'requires_all_correct' => true,
            'accuracy_label' => $session->total_questions > 0 && $session->correct_answers === $session->total_questions
                ? 'Mastered'
                : 'Needs review',
            'study_day' => $session->studyDay ? [
                'id' => $session->studyDay->id,
                'day_number' => $session->studyDay->day_number,
                'title' => $session->studyDay->title,
            ] : null,
            'answers' => $session->answers->values()->map(fn (PracticeAnswer $answer, int $index): array => [
                'id' => $answer->id,
                'position' => $index + 1,
                'section_type' => $answer->question->section_type->value,
                'question_type' => $answer->question->question_type->value,
                'question' => $answer->question->question_text,
                'is_correct' => $answer->is_correct,
                'selected' => $answer->selectedOption?->option_text,
                'correct' => $answer->question->correctOption?->option_text,
                'explanation' => $answer->question->explanation,
            ])->values()->all(),
        ];
    }

    private function questionQuery(SkillType $section, ?int $studyDayId): Builder
    {
        $query = Question::query()
            ->with('options')
            ->whereIn('status', Question::ACTIVE_STATUSES)
            ->inRandomOrder();

        if ($section === SkillType::Mixed) {
            $query->whereIn('section_type', [SkillType::Listening, SkillType::Structure, SkillType::Reading]);
        } else {
            $query->where('section_type', $section);
        }

        if ($studyDayId) {
            $studyDay = StudyDay::query()->with('lesson')->findOrFail($studyDayId);

            if ($studyDay->lesson) {
                $query->where('lesson_id', $studyDay->lesson->id);
            }
        }

        return $query;
    }

    /**
     * @return Collection<int, Question>
     */
    private function questionsForSession(SkillType $section, ?int $studyDayId, int $questionCount): Collection
    {
        $questions = $this->questionQuery($section, $studyDayId)
            ->limit($questionCount)
            ->get();

        if ($studyDayId === null || $questions->count() >= $questionCount) {
            return $questions;
        }

        $supplement = $this->questionQuery($section, null)
            ->whereNotIn('id', $questions->pluck('id')->all())
            ->limit($questionCount - $questions->count())
            ->get();

        return $questions->concat($supplement)->values();
    }

    private function ensureOwner(User $user, PracticeSession $session): void
    {
        if ($session->user_id !== $user->id) {
            throw new AuthorizationException();
        }
    }
}
