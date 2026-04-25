<?php

use App\Models\MistakeJournal;
use App\Models\PracticeSession;
use App\Models\StudyDay;
use App\Models\User;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('users can start answer finish practice and create mistakes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('practice.start'), [
            'section_type' => 'structure',
            'mode' => 'quick',
            'question_count' => 1,
        ])
        ->assertRedirect();

    $session = PracticeSession::query()
        ->whereBelongsTo($user)
        ->with('answers.question.options')
        ->firstOrFail();

    $answer = $session->answers->first();
    $wrongOption = $answer->question->options->firstWhere('is_correct', false);
    $correctOption = $answer->question->options->firstWhere('is_correct', true);

    $this->actingAs($user)
        ->post(route('practice.answer', $session), [
            'question_id' => $answer->question_id,
            'selected_option_id' => $wrongOption->id,
            'time_spent_seconds' => 12,
        ])
        ->assertRedirect(route('practice.show', $session));

    $this->actingAs($user)
        ->get(route('practice.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('practice/show')
            ->where('session.answered_count', 1)
            ->where('session.progress_percent', 100)
            ->where('session.questions.0.position', 1)
            ->where('session.questions.0.is_correct', false)
            ->where('session.questions.0.selected_option_id', $wrongOption->id)
            ->has('session.questions.0.section_type')
            ->has('session.questions.0.question_type')
            ->has('session.questions.0.difficulty')
            ->has('session.questions.0.correct_option_text')
            ->has('session.questions.0.explanation'));

    $this->actingAs($user)
        ->post(route('practice.answer', $session), [
            'question_id' => $answer->question_id,
            'selected_option_id' => $correctOption->id,
            'time_spent_seconds' => 8,
        ])
        ->assertRedirect(route('practice.show', $session));

    $this->actingAs($user)
        ->get(route('practice.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.questions.0.is_correct', false)
            ->where('session.questions.0.selected_option_id', $wrongOption->id));

    $this->actingAs($user)
        ->post(route('practice.finish', $session))
        ->assertRedirect(route('practice.result', $session));

    $session->refresh();

    expect($session->finished_at)->not->toBeNull()
        ->and($session->score)->toBe(0.0)
        ->and(MistakeJournal::query()->whereBelongsTo($user)->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('practice.result', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.passed', false)
            ->where('result.passing_score', 100)
            ->where('result.wrong_answers', 1)
            ->where('result.accuracy_label', 'Needs review')
            ->where('result.answers.0.position', 1)
            ->has('result.answers.0.question_type'));
});

test('quick practice ignores active study day and can start fifty questions', function () {
    $user = User::factory()->create();
    $day = StudyDay::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('practice.start'), [
            'section_type' => 'structure',
            'mode' => 'quick',
            'study_day_id' => $day->id,
            'question_count' => 50,
        ])
        ->assertRedirect();

    $session = PracticeSession::query()
        ->whereBelongsTo($user)
        ->firstOrFail();

    expect($session->study_day_id)->toBeNull()
        ->and($session->total_questions)->toBe(50)
        ->and($session->answers()->count())->toBe(50);
});

test('lesson practice can supplement active day to fifty questions', function () {
    $user = User::factory()->create();
    $day = StudyDay::query()
        ->where('focus_skill', 'structure')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('practice.start'), [
            'section_type' => 'structure',
            'mode' => 'lesson',
            'study_day_id' => $day->id,
            'question_count' => 50,
        ])
        ->assertRedirect();

    $session = PracticeSession::query()
        ->whereBelongsTo($user)
        ->firstOrFail();

    expect($session->study_day_id)->toBe($day->id)
        ->and($session->total_questions)->toBe(50)
        ->and($session->answers()->count())->toBe(50);
});
