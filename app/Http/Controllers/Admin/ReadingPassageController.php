<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReadingPassageRequest;
use App\Http\Requests\Admin\UpdateReadingPassageRequest;
use App\Models\Passage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadingPassageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'difficulty' => (string) $request->query('difficulty', ''),
            'sort' => (string) $request->query('sort', 'newest'),
        ];

        $passages = Passage::query()
            ->withCount('questions')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('title', 'like', "%{$filters['search']}%")
                        ->orWhere('topic', 'like', "%{$filters['search']}%")
                        ->orWhere('body', 'like', "%{$filters['search']}%");
                });
            })
            ->when(in_array($filters['status'], Passage::STATUSES, true), fn ($query) => $query->where('status', $filters['status']))
            ->when(in_array($filters['difficulty'], Passage::DIFFICULTIES, true), fn ($query) => $query->where('difficulty', $filters['difficulty']));

        match ($filters['sort']) {
            'title' => $passages->orderBy('title'),
            'word_count_asc' => $passages->orderBy('word_count'),
            'word_count_desc' => $passages->orderByDesc('word_count'),
            'oldest' => $passages->oldest(),
            default => $passages->latest(),
        };

        return Inertia::render('admin/reading-passages/index', [
            'passages' => $passages
                ->paginate(12)
                ->withQueryString()
                ->through(fn (Passage $passage): array => $this->passagePayload($passage)),
            'filters' => $filters,
            'options' => $this->formOptions(),
            'stats' => [
                'total' => Passage::query()->count(),
                'published' => Passage::query()->where('status', 'published')->count(),
                'short' => Passage::query()->where('word_count', '<', 300)->count(),
                'long' => Passage::query()->where('word_count', '>', 700)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/reading-passages/create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreReadingPassageRequest $request): RedirectResponse
    {
        $passage = Passage::query()->create($this->attributesFromRequest($request));

        return redirect()
            ->route('admin.reading-passages.show', $passage)
            ->with('success', 'Reading passage created.');
    }

    public function show(Passage $readingPassage): Response
    {
        $readingPassage->loadCount('questions');
        $readingPassage->load(['questions' => fn ($query) => $query->latest()->limit(20)]);

        return Inertia::render('admin/reading-passages/show', [
            'passage' => $this->passagePayload($readingPassage, includeBody: true),
            'questions' => $readingPassage->questions->map(fn ($question): array => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type?->value ?? (string) $question->question_type,
                'difficulty' => $question->difficulty,
                'exam_eligible' => $question->exam_eligible,
            ]),
        ]);
    }

    public function edit(Passage $readingPassage): Response
    {
        $readingPassage->loadCount('questions');

        return Inertia::render('admin/reading-passages/edit', [
            'passage' => $this->passagePayload($readingPassage, includeBody: true),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(UpdateReadingPassageRequest $request, Passage $readingPassage): RedirectResponse
    {
        $readingPassage->update($this->attributesFromRequest($request, $readingPassage));

        return redirect()
            ->route('admin.reading-passages.show', $readingPassage)
            ->with('success', 'Reading passage updated.');
    }

    public function destroy(Passage $readingPassage): RedirectResponse
    {
        if ($readingPassage->questions()->exists()) {
            return back()->withErrors([
                'passage' => 'This passage already has questions, so it cannot be deleted safely.',
            ]);
        }

        $readingPassage->delete();

        return redirect()
            ->route('admin.reading-passages.index')
            ->with('success', 'Reading passage deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFromRequest(StoreReadingPassageRequest $request, ?Passage $passage = null): array
    {
        $data = $request->validated();
        $status = $data['status'];

        return [
            'title' => $data['title'],
            'topic' => $data['topic'] ?? null,
            'body' => $data['passage_text'],
            'word_count' => Passage::countWords($data['passage_text']),
            'difficulty' => $data['difficulty'],
            'source' => $passage?->source ?? 'admin-cms',
            'status' => $status,
            'reviewed_at' => in_array($status, ['ready', 'published', 'reviewed'], true)
                ? ($passage?->reviewed_at ?? now())
                : $passage?->reviewed_at,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function formOptions(): array
    {
        return [
            'difficulties' => Passage::DIFFICULTIES,
            'statuses' => Passage::STATUSES,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function passagePayload(Passage $passage, bool $includeBody = false): array
    {
        $payload = [
            'id' => $passage->id,
            'title' => $passage->title,
            'topic' => $passage->topic,
            'difficulty' => $passage->difficulty,
            'status' => $passage->status,
            'word_count' => $passage->word_count,
            'source' => $passage->source,
            'questions_count' => $passage->questions_count ?? 0,
            'created_at' => $passage->created_at->toDateString(),
            'updated_at' => $passage->updated_at->toDateString(),
            'quality_warnings' => $this->qualityWarnings($passage->word_count),
        ];

        if ($includeBody) {
            $payload['passage_text'] = $passage->body;
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function qualityWarnings(int $wordCount): array
    {
        return match (true) {
            $wordCount < 300 => ['This passage is shorter than the TOEFL-style target of 300 words.'],
            $wordCount > 700 => ['This passage is longer than the TOEFL-style target of 700 words.'],
            default => [],
        };
    }
}
