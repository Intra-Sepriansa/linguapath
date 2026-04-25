<?php

use App\Models\PracticeSession;
use App\Models\StudyDay;
use App\Models\StudyLog;
use App\Models\User;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('authenticated users can view the study path', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('study-path.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('study-path/index')
            ->has('path.weeks'));
});

test('lesson pages expose detailed learning content before practice', function () {
    $user = User::factory()->create();
    $day = StudyDay::query()
        ->where('day_number', 12)
        ->firstOrFail();

    $response = $this->actingAs($user)
        ->get(route('lessons.show', $day))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('lessons/show')
            ->where('day.day_number', 12)
            ->where('day.title', 'Parallel Structure')
            ->has('lesson.content.guided_steps', 3)
            ->has('lesson.content.examples', 2)
            ->has('lesson.content.advanced_notes', 3)
            ->has('lesson.content.common_traps', 3)
            ->has('lesson.content.checklist', 4));

    expect($response->inertiaProps('lesson.content.concept'))
        ->toContain('Intinya')
        ->and($response->inertiaProps('lesson.content.guided_steps.0'))
        ->toContain('Cari sinyal penghubung')
        ->and($response->inertiaProps('lesson.content.examples.0.why'))
        ->toContain('kalimat menjadi sejajar');
});

test('first two grammar foundation weeks include Indonesian lessons and daily mini tests', function () {
    $days = StudyDay::query()
        ->with(['lesson.questions.options'])
        ->whereBetween('day_number', [1, 14])
        ->orderBy('day_number')
        ->get();

    expect($days)->toHaveCount(14);

    foreach ($days as $day) {
        expect($day->lesson)->not()->toBeNull();

        $content = $day->lesson->content;

        expect($content['goal'])->not()->toBeEmpty()
            ->and($content['concept'])->not()->toBeEmpty()
            ->and($content['guided_steps'])->toHaveCount(3)
            ->and($content['examples'])->toHaveCount(2)
            ->and($content['advanced_notes'])->toHaveCount(3)
            ->and($content['common_traps'])->toHaveCount(3)
            ->and($content['tasks'])->toHaveCount(3)
            ->and($content['checklist'])->toHaveCount(4)
            ->and($content['practice_items'])->toHaveCount(2);

        expect($day->lesson->questions)->toHaveCount(12);

        foreach ($day->lesson->questions as $question) {
            expect($question->explanation)->not()->toBeEmpty()
                ->and($question->options)->toHaveCount(4)
                ->and($question->options->where('is_correct', true))->toHaveCount(1);
        }
    }
});

test('remaining study path days include complete lessons direct practice and mini tests', function () {
    $days = StudyDay::query()
        ->with(['lesson.questions.options'])
        ->whereBetween('day_number', [15, 60])
        ->orderBy('day_number')
        ->get();

    expect($days)->toHaveCount(46);

    foreach ($days as $day) {
        expect($day->lesson)->not()->toBeNull();

        $content = $day->lesson->content;

        expect($content['goal'])->not()->toBeEmpty()
            ->and($content['concept'])->not()->toBeEmpty()
            ->and($content['coach_note'])->not()->toBeEmpty()
            ->and($content['pattern'])->not()->toBeEmpty()
            ->and($content['guided_steps'])->toHaveCount(3)
            ->and($content['examples'])->toHaveCount(2)
            ->and($content['advanced_notes'])->toHaveCount(3)
            ->and($content['common_traps'])->toHaveCount(3)
            ->and($content['tasks'])->toHaveCount(3)
            ->and($content['checklist'])->toHaveCount(4)
            ->and($content['practice_items'])->toHaveCount(2);

        foreach ($content['practice_items'] as $practiceItem) {
            expect(['choice', 'rewrite'])->toContain($practiceItem['type'])
                ->and($practiceItem['prompt'])->not()->toBeEmpty()
                ->and($practiceItem['instruction'])->not()->toBeEmpty()
                ->and($practiceItem['correct_answer'])->not()->toBeEmpty()
                ->and($practiceItem['explanation'])->not()->toBeEmpty()
                ->and($practiceItem['success_message'])->not()->toBeEmpty()
                ->and($practiceItem['retry_message'])->not()->toBeEmpty();
        }

        expect($day->lesson->questions)->toHaveCount(12);

        foreach ($day->lesson->questions as $question) {
            expect($question->explanation)->not()->toBeEmpty()
                ->and($question->options)->toHaveCount(4)
                ->and($question->options->where('is_correct', true))->toHaveCount(1);
        }
    }
});

test('users cannot complete a study day without a perfect mini test', function () {
    $user = User::factory()->create();
    $day = StudyDay::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('study-days.complete', $day))
        ->assertSessionHasErrors('study_day');

    expect(StudyLog::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($day)
        ->where('completed_lessons', 1)
        ->exists())->toBeFalse();
});

test('study days only pass when the lesson mini test is answered perfectly', function () {
    $user = User::factory()->create();
    $day = StudyDay::query()
        ->with('lesson.questions.options')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('practice.start'), [
            'section_type' => $day->focus_skill->value,
            'mode' => 'lesson',
            'study_day_id' => $day->id,
            'question_count' => $day->lesson->questions->count(),
        ])
        ->assertRedirect();

    $failedSession = PracticeSession::query()
        ->whereBelongsTo($user)
        ->whereNull('finished_at')
        ->orderByDesc('id')
        ->with('answers.question.options')
        ->firstOrFail();

    foreach ($failedSession->answers as $index => $answer) {
        $option = $index === 0
            ? $answer->question->options->firstWhere('is_correct', false)
            : $answer->question->options->firstWhere('is_correct', true);

        $this->actingAs($user)
            ->post(route('practice.answer', $failedSession), [
                'question_id' => $answer->question_id,
                'selected_option_id' => $option->id,
            ])
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('practice.finish', $failedSession))
        ->assertRedirect();

    expect(StudyLog::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($day)
        ->where('completed_lessons', 1)
        ->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('study-path.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('path.completed_days', 0)
            ->where('path.weeks.0.days.0.assessment.status', 'failed'));

    $this->actingAs($user)
        ->post(route('practice.start'), [
            'section_type' => $day->focus_skill->value,
            'mode' => 'lesson',
            'study_day_id' => $day->id,
            'question_count' => $day->lesson->questions->count(),
        ])
        ->assertRedirect();

    $passedSession = PracticeSession::query()
        ->whereBelongsTo($user)
        ->whereNull('finished_at')
        ->orderByDesc('id')
        ->with('answers.question.options')
        ->firstOrFail();

    foreach ($passedSession->answers as $answer) {
        $this->actingAs($user)
            ->post(route('practice.answer', $passedSession), [
                'question_id' => $answer->question_id,
                'selected_option_id' => $answer->question->options->firstWhere('is_correct', true)->id,
            ])
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('practice.finish', $passedSession))
        ->assertRedirect();

    expect(StudyLog::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($day)
        ->where('completed_lessons', 1)
        ->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('study-path.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('path.completed_days', 1)
            ->where('path.weeks.0.days.0.assessment.status', 'passed')
            ->where('path.weeks.0.days.0.assessment.score', 100));
});
