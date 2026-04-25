<?php

namespace App\Services;

use App\Models\SpeakingAttempt;
use App\Models\SpeakingPrompt;
use App\Models\User;

class SpeakingService
{
    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        return [
            'prompts' => SpeakingPrompt::query()
                ->where('is_active', true)
                ->orderBy('skill_level')
                ->orderBy('prompt_type')
                ->limit(12)
                ->get()
                ->map(fn (SpeakingPrompt $prompt): array => $this->promptPayload($prompt))
                ->all(),
            'recent_attempts' => SpeakingAttempt::query()
                ->whereBelongsTo($user)
                ->with('prompt')
                ->latest('attempted_at')
                ->limit(8)
                ->get()
                ->map(fn (SpeakingAttempt $attempt): array => [
                    'id' => $attempt->id,
                    'prompt_title' => $attempt->prompt->title,
                    'duration_seconds' => $attempt->duration_seconds,
                    'word_count' => $attempt->word_count,
                    'confidence_score' => $attempt->confidence_score,
                    'fluency_score' => $attempt->fluency_score,
                    'attempted_at' => $attempt->attempted_at?->diffForHumans(),
                ])
                ->all(),
            'summary' => $this->summary($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeAttempt(User $user, array $data): SpeakingAttempt
    {
        $transcript = trim((string) ($data['transcript'] ?? ''));
        $wordCount = $this->wordCount($transcript);
        $duration = (int) ($data['duration_seconds'] ?? 0);
        $fillerCount = $this->fillerCount($transcript);
        $selfRating = (int) ($data['self_rating'] ?? 3);
        $fluency = $this->fluencyScore($wordCount, $duration, $fillerCount);
        $confidence = min(100, max(20, ($selfRating * 16) + min(20, (int) round($duration / 3))));

        return SpeakingAttempt::query()->create([
            'user_id' => $user->id,
            'speaking_prompt_id' => $data['speaking_prompt_id'],
            'transcript' => $transcript ?: null,
            'duration_seconds' => $duration,
            'word_count' => $wordCount,
            'filler_word_count' => $fillerCount,
            'pronunciation_score' => $transcript ? min(100, 55 + min(25, $wordCount)) : 0,
            'fluency_score' => $fluency,
            'grammar_score' => $transcript ? $this->grammarHeuristic($transcript) : 0,
            'vocabulary_score' => $transcript ? min(100, 50 + min(35, count(array_unique(str_word_count(strtolower($transcript), 1))))) : 0,
            'confidence_score' => $confidence,
            'self_rating' => $selfRating,
            'feedback' => [
                'next_action' => $fillerCount > 3
                    ? 'Repeat the prompt once more and reduce filler words.'
                    : 'Replay your recording and compare clarity with the sample answer.',
                'rubric_note' => 'Hybrid v1 feedback uses transcript, timing, word count, and self-rating.',
            ],
            'attempted_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function promptPayload(SpeakingPrompt $prompt): array
    {
        return [
            'id' => $prompt->id,
            'title' => $prompt->title,
            'prompt_type' => $prompt->prompt_type,
            'skill_level' => $prompt->skill_level,
            'prompt' => $prompt->prompt,
            'sample_answer' => $prompt->sample_answer,
            'focus_points' => $prompt->focus_points ?? [],
            'preparation_seconds' => $prompt->preparation_seconds,
            'response_seconds' => $prompt->response_seconds,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function summary(User $user): array
    {
        $attempts = SpeakingAttempt::query()->whereBelongsTo($user)->get();

        return [
            'attempts' => $attempts->count(),
            'minutes' => (int) ceil($attempts->sum('duration_seconds') / 60),
            'average_fluency' => (int) round((float) $attempts->avg('fluency_score')),
            'average_confidence' => (int) round((float) $attempts->avg('confidence_score')),
        ];
    }

    private function wordCount(string $text): int
    {
        return str_word_count($text);
    }

    private function fillerCount(string $text): int
    {
        preg_match_all('/\b(um|uh|like|actually|basically|you know)\b/i', $text, $matches);

        return count($matches[0]);
    }

    private function fluencyScore(int $wordCount, int $duration, int $fillerCount): int
    {
        if ($duration <= 0) {
            return 0;
        }

        $wordsPerMinute = ($wordCount / max($duration, 1)) * 60;

        return min(100, max(20, (int) round($wordsPerMinute) - ($fillerCount * 4)));
    }

    private function grammarHeuristic(string $text): int
    {
        $sentenceCount = max(1, preg_match_all('/[.!?]/', $text));
        $wordCount = max(1, $this->wordCount($text));
        $averageSentenceLength = $wordCount / $sentenceCount;

        return min(100, max(45, 60 + (int) min(25, $averageSentenceLength)));
    }
}
