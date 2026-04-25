<?php

use App\Models\SpeakingAttempt;
use App\Models\SpeakingPrompt;
use App\Models\User;
use App\Models\WritingPrompt;
use App\Models\WritingSubmission;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('users can open speaking room and save a speaking attempt', function () {
    $user = User::factory()->create();
    $prompt = SpeakingPrompt::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('speaking.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('speaking/index')
            ->has('speaking.prompts')
            ->has('speaking.summary'));

    $this->actingAs($user)
        ->post(route('speaking.attempts.store'), [
            'speaking_prompt_id' => $prompt->id,
            'transcript' => 'My name is Andi. I study English every day because I want to improve my TOEFL score.',
            'duration_seconds' => 42,
            'self_rating' => 4,
        ])
        ->assertRedirect();

    $attempt = SpeakingAttempt::query()->whereBelongsTo($user)->firstOrFail();

    expect($attempt->word_count)->toBeGreaterThan(10)
        ->and($attempt->fluency_score)->toBeGreaterThan(0)
        ->and($attempt->confidence_score)->toBeGreaterThan(0);
});

test('users can open writing room and save a writing submission with rubric scores', function () {
    $user = User::factory()->create();
    $prompt = WritingPrompt::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('writing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('writing/index')
            ->has('writing.prompts')
            ->has('writing.summary'));

    $this->actingAs($user)
        ->post(route('writing.submissions.store'), [
            'writing_prompt_id' => $prompt->id,
            'response_text' => 'I study English every evening. I review vocabulary, practice grammar, and write short answers. This habit helps me remember mistakes and improve slowly.',
        ])
        ->assertRedirect();

    $submission = WritingSubmission::query()->whereBelongsTo($user)->firstOrFail();

    expect($submission->word_count)->toBeGreaterThan(15)
        ->and($submission->overall_score)->toBeGreaterThan(0)
        ->and($submission->feedback)->toHaveKey('next_action');
});
