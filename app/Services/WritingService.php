<?php

namespace App\Services;

use App\Models\User;
use App\Models\WritingPrompt;
use App\Models\WritingSubmission;

class WritingService
{
    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        return [
            'prompts' => WritingPrompt::query()
                ->where('is_active', true)
                ->orderBy('skill_level')
                ->orderBy('prompt_type')
                ->limit(12)
                ->get()
                ->map(fn (WritingPrompt $prompt): array => [
                    'id' => $prompt->id,
                    'title' => $prompt->title,
                    'prompt_type' => $prompt->prompt_type,
                    'skill_level' => $prompt->skill_level,
                    'prompt' => $prompt->prompt,
                    'suggested_minutes' => $prompt->suggested_minutes,
                    'min_words' => $prompt->min_words,
                    'rubric' => $prompt->rubric ?? [],
                    'sample_response' => $prompt->sample_response,
                ])
                ->all(),
            'recent_submissions' => WritingSubmission::query()
                ->whereBelongsTo($user)
                ->with('prompt')
                ->latest('submitted_at')
                ->limit(8)
                ->get()
                ->map(fn (WritingSubmission $submission): array => [
                    'id' => $submission->id,
                    'prompt_title' => $submission->prompt->title,
                    'word_count' => $submission->word_count,
                    'overall_score' => $submission->overall_score,
                    'submitted_at' => $submission->submitted_at?->diffForHumans(),
                ])
                ->all(),
            'summary' => $this->summary($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeSubmission(User $user, array $data): WritingSubmission
    {
        $response = trim((string) $data['response_text']);
        $wordCount = str_word_count($response);
        $sentenceCount = max(1, preg_match_all('/[.!?]/', $response));
        $uniqueWords = count(array_unique(str_word_count(strtolower($response), 1)));
        $taskScore = min(100, max(35, (int) round(($wordCount / 120) * 100)));
        $grammarScore = min(100, max(40, 55 + min(30, (int) round($wordCount / $sentenceCount))));
        $vocabularyScore = min(100, max(40, 45 + min(40, $uniqueWords)));
        $coherenceScore = min(100, max(45, 55 + ($sentenceCount * 5)));
        $overall = (int) round(($taskScore + $grammarScore + $vocabularyScore + $coherenceScore) / 4);

        return WritingSubmission::query()->create([
            'user_id' => $user->id,
            'writing_prompt_id' => $data['writing_prompt_id'],
            'response_text' => $response,
            'word_count' => $wordCount,
            'task_score' => $taskScore,
            'grammar_score' => $grammarScore,
            'vocabulary_score' => $vocabularyScore,
            'coherence_score' => $coherenceScore,
            'overall_score' => $overall,
            'feedback' => [
                'next_action' => $wordCount < 80
                    ? 'Expand your answer with one reason and one example.'
                    : 'Rewrite one sentence to make the idea clearer and more academic.',
                'rubric_note' => 'Hybrid v1 feedback uses length, sentence balance, vocabulary range, and coherence signals.',
            ],
            'submitted_at' => now(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function summary(User $user): array
    {
        $submissions = WritingSubmission::query()->whereBelongsTo($user)->get();

        return [
            'submissions' => $submissions->count(),
            'words' => (int) $submissions->sum('word_count'),
            'average_score' => (int) round((float) $submissions->avg('overall_score')),
        ];
    }
}
