<?php

use App\Enums\MistakeType;
use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Models\MistakeJournal;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('authenticated users can view mistake journal workspace data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    MistakeJournal::factory()->create([
        'user_id' => $user->id,
        'question_id' => Question::factory([
            'question_text' => 'The committee ___ every Friday.',
        ]),
        'section_type' => SkillType::Structure,
        'mistake_type' => MistakeType::Grammar,
        'user_answer' => 'meet',
        'correct_answer' => 'meets',
        'note' => 'Collective singular subject uses a singular verb.',
        'review_status' => ReviewStatus::New,
        'created_at' => now()->subDays(2),
    ]);

    $latestMistake = MistakeJournal::factory()->create([
        'user_id' => $user->id,
        'question_id' => Question::factory([
            'question_text' => 'What does the speaker imply about the deadline?',
        ]),
        'section_type' => SkillType::Listening,
        'mistake_type' => MistakeType::Listening,
        'user_answer' => 'It was cancelled.',
        'correct_answer' => 'It was moved earlier.',
        'note' => 'The speaker says the timeline was pulled forward.',
        'review_status' => ReviewStatus::Reviewing,
        'created_at' => now()->subDay(),
    ]);

    MistakeJournal::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('mistakes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mistakes/index')
            ->has('mistakes', 2)
            ->where('mistakes.0.id', $latestMistake->id)
            ->where('mistakes.0.section_type', 'listening')
            ->where('mistakes.0.mistake_type', 'listening')
            ->where('mistakes.0.review_status', 'reviewing')
            ->where('mistakes.0.question', 'What does the speaker imply about the deadline?')
            ->where('mistakes.0.user_answer', 'It was cancelled.')
            ->where('mistakes.0.correct_answer', 'It was moved earlier.')
            ->where('mistakes.0.note', 'The speaker says the timeline was pulled forward.')
            ->where('mistakes.0.created_at', now()->subDay()->toDateString()));

    expect(collect($response->inertiaProps('mistakes'))->pluck('id'))->not->toContain(
        MistakeJournal::query()->whereBelongsTo($otherUser)->value('id')
    );
});

test('users can update their own mistake review status', function () {
    $user = User::factory()->create();
    $mistake = MistakeJournal::factory()->create([
        'user_id' => $user->id,
        'review_status' => ReviewStatus::New,
        'reviewed_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('mistakes.review', $mistake), [
            'review_status' => 'reviewing',
        ])
        ->assertRedirect();

    expect($mistake->fresh())
        ->review_status->toBe(ReviewStatus::Reviewing)
        ->reviewed_at->not->toBeNull();
});

test('users cannot update another users mistake', function () {
    $user = User::factory()->create();
    $mistake = MistakeJournal::factory()->create([
        'review_status' => ReviewStatus::New,
    ]);

    $this->actingAs($user)
        ->patch(route('mistakes.review', $mistake), [
            'review_status' => 'fixed',
        ])
        ->assertForbidden();

    expect($mistake->fresh()->review_status)->toBe(ReviewStatus::New);
});
