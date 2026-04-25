<?php

namespace App\Services;

use App\Enums\VocabularyStatus;
use App\Models\User;
use App\Models\UserVocabulary;
use App\Models\UserVocabularyReview;
use App\Models\Vocabulary;

class VocabularyService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function daily(User $user, int $limit = 72): array
    {
        $dueVocabularyIds = UserVocabularyReview::query()
            ->whereBelongsTo($user)
            ->where(function ($query): void {
                $query
                    ->whereIn('status', [VocabularyStatus::Weak, VocabularyStatus::ReviewLater])
                    ->orWhereNull('due_at')
                    ->orWhere('due_at', '<=', now());
            })
            ->where('status', '!=', VocabularyStatus::Mastered)
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit($limit)
            ->pluck('vocabulary_id');

        $reviewWords = Vocabulary::query()
            ->with([
                'userVocabularyReviews' => fn ($query) => $query->whereBelongsTo($user),
                'userVocabularies' => fn ($query) => $query->whereBelongsTo($user),
            ])
            ->whereIn('id', $dueVocabularyIds)
            ->get()
            ->sortBy(fn (Vocabulary $vocabulary): int => $dueVocabularyIds->search($vocabulary->id))
            ->values();

        $remaining = max(0, $limit - $reviewWords->count());
        $newWords = Vocabulary::query()
            ->with([
                'userVocabularyReviews' => fn ($query) => $query->whereBelongsTo($user),
                'userVocabularies' => fn ($query) => $query->whereBelongsTo($user),
            ])
            ->whereNotIn('id', $reviewWords->pluck('id')->all())
            ->whereDoesntHave('userVocabularyReviews', fn ($query) => $query
                ->whereBelongsTo($user)
                ->where('status', VocabularyStatus::Mastered))
            ->whereDoesntHave('userVocabularies', fn ($query) => $query
                ->whereBelongsTo($user)
                ->where('status', VocabularyStatus::Mastered))
            ->orderByRaw('frequency_rank is null')
            ->orderBy('frequency_rank')
            ->inRandomOrder()
            ->limit($remaining)
            ->get();

        $vocabularies = $reviewWords->concat($newWords)->values();

        $meaningPool = Vocabulary::query()
            ->whereNotIn('id', $vocabularies->pluck('id')->all())
            ->inRandomOrder()
            ->limit(180)
            ->pluck('meaning')
            ->all();

        return $vocabularies
            ->map(fn (Vocabulary $vocabulary): array => $this->payload($vocabulary, $meaningPool))
            ->all();
    }

    public function mark(User $user, Vocabulary $vocabulary, VocabularyStatus $status): UserVocabulary
    {
        $this->recordReview($user, $vocabulary, $status);

        $record = UserVocabulary::query()->firstOrNew([
            'user_id' => $user->id,
            'vocabulary_id' => $vocabulary->id,
        ]);

        $record->status = $status;
        $record->review_count = $record->exists ? $record->review_count + 1 : 1;
        $record->last_reviewed_at = now();
        $record->save();

        return $record;
    }

    /**
     * @return array<string, int|array<int, array<string, int|string>>>
     */
    public function summary(User $user): array
    {
        $total = Vocabulary::query()->count();
        $learning = UserVocabulary::query()->whereBelongsTo($user)->where('status', VocabularyStatus::Learning)->count();
        $mastered = UserVocabulary::query()->whereBelongsTo($user)->where('status', VocabularyStatus::Mastered)->count();
        $weak = UserVocabulary::query()->whereBelongsTo($user)->where('status', VocabularyStatus::Weak)->count();
        $reviewLater = UserVocabulary::query()->whereBelongsTo($user)->where('status', VocabularyStatus::ReviewLater)->count();
        $dueReviews = UserVocabularyReview::query()
            ->whereBelongsTo($user)
            ->where('status', '!=', VocabularyStatus::Mastered)
            ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '<=', now()))
            ->count();
        $averageEase = (float) UserVocabularyReview::query()
            ->whereBelongsTo($user)
            ->avg('ease_score');

        return [
            'total' => $total,
            'learning' => $learning,
            'mastered' => $mastered,
            'weak' => $weak,
            'review_later' => $reviewLater,
            'available' => max($total - $mastered, 0),
            'mastery_rate' => $total > 0 ? (int) round(($mastered / $total) * 100) : 0,
            'review_queue' => $weak + $reviewLater,
            'due_reviews' => $dueReviews,
            'average_ease' => $averageEase > 0 ? (int) round($averageEase * 40) : 0,
            'categories' => $this->distribution('category'),
            'difficulties' => $this->distribution('difficulty'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Vocabulary $vocabulary, array $meaningPool): array
    {
        /** @var UserVocabulary|null $userVocabulary */
        $userVocabulary = $vocabulary->relationLoaded('userVocabularies')
            ? $vocabulary->userVocabularies->first()
            : null;
        /** @var UserVocabularyReview|null $review */
        $review = $vocabulary->relationLoaded('userVocabularyReviews')
            ? $vocabulary->userVocabularyReviews->first()
            : null;
        $status = $review?->status ?? $userVocabulary?->status ?? VocabularyStatus::Learning;

        return [
            'id' => $vocabulary->id,
            'word' => $vocabulary->word,
            'pronunciation_text' => $vocabulary->pronunciation ?: $this->pronunciationTextFor($vocabulary->word),
            'pronunciation_lookup_terms' => $this->pronunciationLookupTermsFor($vocabulary->word),
            'pronunciation_locale' => 'en-US',
            'meaning' => $vocabulary->meaning,
            'usage_note' => $vocabulary->usage_note ?: $this->usageNoteFor($vocabulary),
            'example_sentence' => $vocabulary->example_sentence,
            'example_translation' => $vocabulary->example_translation ?: $this->exampleTranslationFor($vocabulary),
            'category' => $vocabulary->category,
            'difficulty' => $vocabulary->difficulty,
            'status' => $status->value,
            'status_label' => $this->statusLabel($status),
            'review_count' => $review?->review_count ?? $userVocabulary?->review_count ?? 0,
            'last_reviewed_at' => ($review?->last_reviewed_at ?? $userVocabulary?->last_reviewed_at)?->toIso8601String(),
            'due_at' => $review?->due_at?->toIso8601String(),
            'interval_days' => $review?->interval_days ?? 0,
            'ease_score' => $review?->ease_score ?? 2.5,
            'correct_count' => $review?->correct_count ?? 0,
            'wrong_count' => $review?->wrong_count ?? 0,
            'synonyms' => $vocabulary->synonyms ?? [],
            'antonyms' => $vocabulary->antonyms ?? [],
            'word_family' => $vocabulary->word_family ?? [],
            'collocations' => $vocabulary->collocations ?? [],
            'quiz_options' => $this->quizOptions($vocabulary->meaning, $meaningPool),
        ];
    }

    private function recordReview(User $user, Vocabulary $vocabulary, VocabularyStatus $status): UserVocabularyReview
    {
        $review = UserVocabularyReview::query()->firstOrNew([
            'user_id' => $user->id,
            'vocabulary_id' => $vocabulary->id,
        ]);

        $wasCorrect = $status === VocabularyStatus::Mastered || $status === VocabularyStatus::ReviewLater;
        $previousInterval = max((int) $review->interval_days, 0);
        $previousEase = $review->exists ? (float) $review->ease_score : 2.5;
        $nextInterval = $this->nextInterval($status, $previousInterval, $previousEase);

        $review->status = $status;
        $review->review_count = ($review->review_count ?? 0) + 1;
        $review->correct_count = ($review->correct_count ?? 0) + ($wasCorrect ? 1 : 0);
        $review->wrong_count = ($review->wrong_count ?? 0) + ($wasCorrect ? 0 : 1);
        $review->ease_score = $this->nextEase($status, $previousEase);
        $review->interval_days = $nextInterval;
        $review->due_at = $status === VocabularyStatus::Mastered
            ? now()->addDays($nextInterval)
            : now()->addDays(max(1, $nextInterval));
        $review->last_reviewed_at = now();
        $review->save();

        return $review;
    }

    private function nextInterval(VocabularyStatus $status, int $previousInterval, float $ease): int
    {
        return match ($status) {
            VocabularyStatus::Mastered => max(3, (int) round(max(1, $previousInterval) * max(2.0, $ease))),
            VocabularyStatus::ReviewLater => max(1, $previousInterval > 0 ? $previousInterval : 2),
            VocabularyStatus::Weak => 1,
            VocabularyStatus::Learning => 1,
        };
    }

    private function nextEase(VocabularyStatus $status, float $previousEase): float
    {
        return match ($status) {
            VocabularyStatus::Mastered => min(3.2, $previousEase + 0.15),
            VocabularyStatus::ReviewLater => min(3.0, $previousEase + 0.05),
            VocabularyStatus::Weak => max(1.3, $previousEase - 0.25),
            VocabularyStatus::Learning => max(1.8, $previousEase - 0.05),
        };
    }

    /**
     * @return array<int, string>
     */
    private function quizOptions(string $correctMeaning, array $meaningPool): array
    {
        $distractors = collect($meaningPool)
            ->filter(fn (string $meaning): bool => $meaning !== $correctMeaning)
            ->unique()
            ->take(3)
            ->values();

        return $distractors
            ->push($correctMeaning)
            ->shuffle()
            ->values()
            ->all();
    }

    private function pronunciationTextFor(string $word): string
    {
        $pronunciation = preg_replace('/[\s_\/-]+/', ' ', $word);

        return trim($pronunciation ?? $word);
    }

    /**
     * @return array<int, string>
     */
    private function pronunciationLookupTermsFor(string $word): array
    {
        return collect([$word, $this->pronunciationTextFor($word)])
            ->map(fn (string $term): string => strtolower(trim(preg_replace('/\s+/', ' ', $term) ?? $term)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function statusLabel(VocabularyStatus $status): string
    {
        return match ($status) {
            VocabularyStatus::Learning => 'Learning',
            VocabularyStatus::Mastered => 'Mastered',
            VocabularyStatus::ReviewLater => 'Review Later',
            VocabularyStatus::Weak => 'Weak',
        };
    }

    private function usageNoteFor(Vocabulary $vocabulary): string
    {
        $context = $this->contextLabel($vocabulary->category);

        return "Digunakan untuk memahami kata \"{$vocabulary->word}\" yang berarti \"{$vocabulary->meaning}\" dalam konteks {$context}.";
    }

    private function exampleTranslationFor(Vocabulary $vocabulary): string
    {
        $context = $this->contextLabel($vocabulary->category);

        return "Terjemahan contoh: kalimat tersebut menggunakan \"{$vocabulary->word}\" sebagai \"{$vocabulary->meaning}\" dalam konteks {$context}.";
    }

    private function contextLabel(string $category): string
    {
        return match ($category) {
            'academic' => 'akademik dan ide umum TOEFL',
            'research' => 'riset, bukti, data, dan analisis',
            'transition' => 'penghubung ide antar kalimat',
            'reading' => 'bacaan dan strategi Reading',
            'listening' => 'percakapan, kuliah, dan Listening',
            'structure' => 'grammar, klausa, dan bentuk kata',
            'test-strategy' => 'strategi menjawab dan review TOEFL',
            default => 'latihan TOEFL',
        };
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    private function distribution(string $column): array
    {
        return Vocabulary::query()
            ->selectRaw("{$column} as label, count(*) as count")
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn (Vocabulary $item): array => [
                'label' => (string) $item->getAttribute('label'),
                'count' => (int) $item->getAttribute('count'),
            ])
            ->all();
    }
}
