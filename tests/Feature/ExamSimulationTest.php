<?php

use App\Models\ExamSimulation;
use App\Models\MistakeJournal;
use App\Models\User;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('users can start a full TOEFL ITP simulation with locked sections and hidden transcripts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->with(['sections.answers.question.options'])
        ->firstOrFail();

    expect($simulation->total_questions)->toBe(140)
        ->and($simulation->sections)->toHaveCount(3)
        ->and($simulation->sections->pluck('total_questions')->all())->toBe([50, 40, 50])
        ->and($simulation->sections[0]->status)->toBe('active')
        ->and($simulation->sections[1]->status)->toBe('locked')
        ->and($simulation->answers()->count())->toBe(140);

    $response = $this->actingAs($user)
        ->get(route('exam.show', $simulation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exam/show')
            ->where('exam.current_section.section_type', 'listening')
            ->has('exam.server_now')
            ->has('exam.score_disclaimer')
            ->has('exam.current_section.section_ends_at')
            ->has('exam.questions', 50)
            ->where('exam.questions.0.transcript', null)
            ->where('exam.questions.0.audio_playback_text', null));

    expect($response->inertiaProps('exam.score_disclaimer'))->toContain('not an official ETS score')
        ->and($simulation->sections[0]->ends_at)->not()->toBeNull();
});

test('section lock prevents answering an already completed exam section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->with(['sections.answers.question.options'])
        ->firstOrFail();
    $listeningSection = $simulation->sections->firstWhere('section_type', 'listening');
    $answer = $listeningSection->answers->first();
    $wrongOption = $answer->question->options->firstWhere('is_correct', false);

    $this->actingAs($user)
        ->post(route('exam.answer', $simulation), [
            'answer_id' => $answer->id,
            'selected_option_id' => $wrongOption->id,
            'time_spent_seconds' => 8,
        ])
        ->assertRedirect(route('exam.show', $simulation));

    $this->actingAs($user)
        ->post(route('exam.finish-section', $simulation))
        ->assertRedirect(route('exam.show', $simulation));

    $this->actingAs($user)
        ->post(route('exam.answer', $simulation), [
            'answer_id' => $answer->id,
            'selected_option_id' => $wrongOption->id,
        ])
        ->assertSessionHasErrors('section');
});

test('expired sections reject late answers and can be submitted idempotently', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->with(['sections.answers.question.options'])
        ->firstOrFail();
    $section = $simulation->sections->firstWhere('section_type', 'listening');
    $answer = $section->answers->first();
    $option = $answer->question->options->first();

    $section->update(['ends_at' => now()->subSecond()]);

    $this->actingAs($user)
        ->post(route('exam.answer', $simulation), [
            'answer_id' => $answer->id,
            'selected_option_id' => $option->id,
        ])
        ->assertSessionHasErrors('section');

    expect($answer->fresh()->selected_option_id)->toBeNull();

    $this->actingAs($user)
        ->post(route('exam.finish-section', $simulation))
        ->assertRedirect(route('exam.show', $simulation));

    $this->actingAs($user)
        ->post(route('exam.finish-section', $simulation))
        ->assertRedirect();

    $section->refresh();

    expect($section->status)->toBe('completed')
        ->and($section->submission_reason)->toBe('timed_out')
        ->and($section->submitted_at)->not()->toBeNull();
});

test('finishing an exam produces estimated scaled scores and exam mistake journal entries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('exam.finish', $simulation))
        ->assertRedirect(route('exam.result', $simulation));

    $simulation->refresh();

    expect($simulation->status)->toBe('completed')
        ->and($simulation->estimated_total_score)->toBe(310)
        ->and(MistakeJournal::query()->whereBelongsTo($user)->count())->toBe(140);

    $this->actingAs($user)
        ->get(route('exam.result', $simulation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exam/result')
            ->where('result.estimated_total_score', 310)
            ->has('result.score_disclaimer')
            ->has('result.sections', 3)
            ->has('result.weaknesses')
            ->has('result.recommendations', 3)
            ->where('result.answers.0.transcript', fn ($value) => is_string($value) || $value === null));
});
