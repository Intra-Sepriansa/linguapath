<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\AudioAsset;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'section_type' => (string) $request->query('section_type', ''),
            'question_type' => (string) $request->query('question_type', ''),
            'difficulty' => (string) $request->query('difficulty', ''),
            'status' => (string) $request->query('status', ''),
            'skill_tag_id' => (string) $request->query('skill_tag_id', ''),
            'quality' => (string) $request->query('quality', ''),
            'sort' => (string) $request->query('sort', 'newest'),
        ];

        $questions = Question::query()
            ->with(['options', 'passage', 'audioAsset', 'skillTag'])
            ->withCount([
                'options',
                'practiceAnswers',
                'examAnswers',
                'options as correct_options_count' => fn (Builder $query) => $query->where('is_correct', true),
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('question_text', 'like', "%{$filters['search']}%")
                        ->orWhere('explanation', 'like', "%{$filters['search']}%");
                });
            })
            ->when(in_array($filters['section_type'], $this->sectionValues(), true), fn (Builder $query) => $query->where('section_type', $filters['section_type']))
            ->when(in_array($filters['question_type'], $this->questionTypeValues(), true), fn (Builder $query) => $query->where('question_type', $filters['question_type']))
            ->when(in_array($filters['difficulty'], Passage::DIFFICULTIES, true), fn (Builder $query) => $query->where('difficulty', $filters['difficulty']))
            ->when(in_array($filters['status'], Question::STATUSES, true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when((int) $filters['skill_tag_id'] > 0, fn (Builder $query) => $query->where('skill_tag_id', (int) $filters['skill_tag_id']));

        $this->applyQualityFilter($questions, $filters['quality']);

        match ($filters['sort']) {
            'oldest' => $questions->oldest(),
            'section' => $questions->orderBy('section_type')->latest(),
            'difficulty' => $questions->orderBy('difficulty')->latest(),
            default => $questions->latest(),
        };

        return Inertia::render('admin/questions/index', [
            'questions' => $questions
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Question $question): array => $this->questionPayload($question)),
            'filters' => $filters,
            'options' => $this->formOptions(),
            'stats' => $this->stats(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/questions/create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $question = DB::transaction(function () use ($request): Question {
            $question = Question::query()->create($this->attributesFromRequest($request));

            $this->syncOptions($question, $request->validated('options'));

            return $question;
        });

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'Question created.');
    }

    public function show(Question $question): Response
    {
        $question->load(['options', 'passage', 'audioAsset', 'skillTag']);
        $question->loadCount(['practiceAnswers', 'examAnswers']);
        $question->loadCount(['options as correct_options_count' => fn (Builder $query) => $query->where('is_correct', true)]);

        return Inertia::render('admin/questions/show', [
            'question' => $this->questionPayload($question, includeContext: true),
        ]);
    }

    public function edit(Question $question): Response
    {
        $question->load(['options', 'passage', 'audioAsset', 'skillTag']);
        $question->loadCount(['practiceAnswers', 'examAnswers']);

        return Inertia::render('admin/questions/edit', [
            'question' => $this->questionPayload($question, includeContext: true),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        DB::transaction(function () use ($request, $question): void {
            $question->update($this->attributesFromRequest($request));
            $this->syncOptions($question, $request->validated('options'));
        });

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->loadCount(['practiceAnswers', 'examAnswers']);

        if ($question->practice_answers_count > 0 || $question->exam_answers_count > 0) {
            $question->update([
                'status' => 'archived',
                'exam_eligible' => false,
            ]);

            return back()->with('success', 'Question archived because it has learner history.');
        }

        $question->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted.');
    }

    private function applyQualityFilter(Builder $query, string $quality): void
    {
        match ($quality) {
            'missing_explanation' => $query->where(fn (Builder $query) => $query->whereNull('explanation')->orWhere('explanation', '')),
            'missing_evidence' => $query
                ->where('section_type', SkillType::Reading)
                ->where(fn (Builder $query) => $query->whereNull('evidence_sentence')->orWhere('evidence_sentence', '')),
            'missing_audio' => $query
                ->where('section_type', SkillType::Listening)
                ->where(fn (Builder $query) => $query->whereNull('audio_asset_id')->orWhereDoesntHave('audioAsset', fn (Builder $audioQuery) => $audioQuery->where('is_real_audio', true))),
            'invalid_options' => $this->applyInvalidOptionsFilter($query),
            default => null,
        };
    }

    private function applyInvalidOptionsFilter(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query
                ->has('options', '!=', 4)
                ->orWhereHas('options', fn (Builder $optionQuery) => $optionQuery->where('is_correct', true), '!=', 1);
        });
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'total' => Question::query()->count(),
            'ready' => Question::query()->where('status', 'ready')->count(),
            'published' => Question::query()->where('status', 'published')->count(),
            'archived' => Question::query()->where('status', 'archived')->count(),
            'invalid_options' => tap(Question::query(), fn (Builder $query) => $this->applyInvalidOptionsFilter($query))->count(),
            'missing_audio' => Question::query()
                ->where('section_type', SkillType::Listening)
                ->whereIn('status', Question::ACTIVE_STATUSES)
                ->where(fn (Builder $query) => $query->whereNull('audio_asset_id')->orWhereDoesntHave('audioAsset', fn (Builder $audioQuery) => $audioQuery->where('is_real_audio', true)))
                ->count(),
            'missing_evidence' => Question::query()
                ->where('section_type', SkillType::Reading)
                ->whereIn('status', Question::ACTIVE_STATUSES)
                ->where(fn (Builder $query) => $query->whereNull('evidence_sentence')->orWhere('evidence_sentence', ''))
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFromRequest(StoreQuestionRequest $request): array
    {
        $data = $request->validated();
        $section = SkillType::from($data['section_type']);
        $status = $data['status'];
        $passage = $section === SkillType::Reading && isset($data['passage_id'])
            ? Passage::query()->find($data['passage_id'])
            : null;
        $audio = $section === SkillType::Listening && isset($data['audio_asset_id'])
            ? AudioAsset::query()->find($data['audio_asset_id'])
            : null;

        return [
            'section_type' => $section,
            'question_type' => $data['question_type'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'status' => $status,
            'exam_eligible' => in_array($status, Question::ACTIVE_STATUSES, true),
            'question_text' => $data['question_text'],
            'passage_id' => $section === SkillType::Reading ? ($data['passage_id'] ?? null) : null,
            'audio_asset_id' => $section === SkillType::Listening ? ($data['audio_asset_id'] ?? null) : null,
            'skill_tag_id' => $data['skill_tag_id'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'evidence_sentence' => $section === SkillType::Reading ? ($data['evidence_sentence'] ?? null) : null,
            'passage_text' => $passage?->body,
            'transcript' => $audio?->transcript,
            'audio_url' => $audio?->playbackUrl(),
            'why_correct' => $data['explanation'] ?? null,
            'why_wrong' => filled($data['explanation'] ?? null)
                ? 'Review the evidence, audio cue, or grammar rule before choosing another option.'
                : null,
        ];
    }

    /**
     * @param  array<int, array{label: string, text: string, is_correct: bool|string|int}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $optionsByLabel = collect($options)->keyBy('label');

        foreach (StoreQuestionRequest::optionLabels() as $label) {
            $option = $optionsByLabel[$label];

            $question->options()->updateOrCreate(
                ['option_label' => $label],
                [
                    'option_text' => $option['text'],
                    'is_correct' => filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }

        $question->options()
            ->whereNotIn('option_label', StoreQuestionRequest::optionLabels())
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'sections' => collect([SkillType::Listening, SkillType::Structure, SkillType::Reading])
                ->map(fn (SkillType $section): array => ['value' => $section->value, 'label' => $section->label()])
                ->all(),
            'questionTypes' => $this->questionTypesBySection(),
            'difficulties' => Passage::DIFFICULTIES,
            'statuses' => Question::STATUSES,
            'qualityFilters' => [
                'missing_explanation',
                'missing_evidence',
                'missing_audio',
                'invalid_options',
            ],
            'passages' => Passage::query()
                ->whereIn('status', ['ready', 'published', 'reviewed'])
                ->orderBy('title')
                ->limit(200)
                ->get(['id', 'title', 'topic', 'difficulty', 'status', 'word_count'])
                ->map(fn (Passage $passage): array => [
                    'id' => $passage->id,
                    'title' => $passage->title,
                    'topic' => $passage->topic,
                    'difficulty' => $passage->difficulty,
                    'status' => $passage->status,
                    'word_count' => $passage->word_count,
                ]),
            'audioAssets' => AudioAsset::query()
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (AudioAsset $asset): array => [
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'status' => $asset->status,
                    'is_real_audio' => $asset->is_real_audio,
                    'duration_seconds' => $asset->duration_seconds,
                    'accent' => $asset->accent,
                    'audio_url' => $asset->playbackUrl(),
                ]),
            'skillTags' => SkillTag::query()
                ->orderBy('domain')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'domain', 'difficulty'])
                ->map(fn (SkillTag $tag): array => [
                    'id' => $tag->id,
                    'code' => $tag->code,
                    'name' => $tag->name,
                    'domain' => $tag->domain,
                    'difficulty' => $tag->difficulty,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(Question $question, bool $includeContext = false): array
    {
        $payload = [
            'id' => $question->id,
            'section_type' => $question->section_type->value,
            'question_type' => $question->question_type?->value,
            'difficulty' => $question->difficulty,
            'status' => $question->status,
            'exam_eligible' => $question->exam_eligible,
            'question_text' => $question->question_text,
            'explanation' => $question->explanation,
            'evidence_sentence' => $question->evidence_sentence,
            'passage_id' => $question->passage_id,
            'audio_asset_id' => $question->audio_asset_id,
            'skill_tag_id' => $question->skill_tag_id,
            'quality_warnings' => $question->qualityWarnings(),
            'options_count' => $question->options_count ?? $question->options->count(),
            'correct_options_count' => $question->correct_options_count ?? $question->options->where('is_correct', true)->count(),
            'practice_answers_count' => $question->practice_answers_count ?? 0,
            'exam_answers_count' => $question->exam_answers_count ?? 0,
            'created_at' => $question->created_at->toDateString(),
            'updated_at' => $question->updated_at->toDateString(),
            'passage' => $question->passage ? [
                'id' => $question->passage->id,
                'title' => $question->passage->title,
                'topic' => $question->passage->topic,
                'word_count' => $question->passage->word_count,
                'status' => $question->passage->status,
            ] : null,
            'audio_asset' => $question->audioAsset ? [
                'id' => $question->audioAsset->id,
                'title' => $question->audioAsset->title,
                'status' => $question->audioAsset->status,
                'is_real_audio' => $question->audioAsset->is_real_audio,
                'duration_seconds' => $question->audioAsset->duration_seconds,
                'audio_url' => $question->audioAsset->playbackUrl(),
            ] : null,
            'skill_tag' => $question->skillTag ? [
                'id' => $question->skillTag->id,
                'name' => $question->skillTag->name,
                'domain' => $question->skillTag->domain,
            ] : null,
            'options' => $question->options->map(fn (QuestionOption $option): array => [
                'id' => $option->id,
                'label' => $option->option_label,
                'text' => $option->option_text,
                'is_correct' => $option->is_correct,
            ])->values(),
        ];

        if ($includeContext && $question->passage) {
            $payload['passage']['body'] = $question->passage->body;
        }

        if ($includeContext && $question->audioAsset) {
            $payload['audio_asset']['transcript'] = $question->audioAsset->transcript;
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function sectionValues(): array
    {
        return [
            SkillType::Listening->value,
            SkillType::Structure->value,
            SkillType::Reading->value,
        ];
    }

    /**
     * @return list<string>
     */
    private function questionTypeValues(): array
    {
        return collect($this->questionTypesBySection())
            ->flatten(1)
            ->pluck('value')
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function questionTypesBySection(): array
    {
        return [
            SkillType::Listening->value => $this->questionTypeOptions([
                QuestionType::ShortConversation,
                QuestionType::LongConversation,
                QuestionType::TalksLectures,
                QuestionType::CampusConversation,
                QuestionType::SpeakerAttitude,
                QuestionType::Function,
            ]),
            SkillType::Structure->value => $this->questionTypeOptions([
                QuestionType::IncompleteSentence,
                QuestionType::ErrorRecognition,
                QuestionType::SentenceCorrection,
            ]),
            SkillType::Reading->value => $this->questionTypeOptions([
                QuestionType::MainIdea,
                QuestionType::Detail,
                QuestionType::VocabularyContext,
                QuestionType::Reference,
                QuestionType::Inference,
                QuestionType::AuthorPurpose,
                QuestionType::SentenceInsertion,
                QuestionType::Summary,
            ]),
        ];
    }

    /**
     * @param  list<QuestionType>  $types
     * @return list<array{value: string, label: string}>
     */
    private function questionTypeOptions(array $types): array
    {
        return collect($types)
            ->map(fn (QuestionType $type): array => [
                'value' => $type->value,
                'label' => str($type->value)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }
}
