<?php

use App\Models\Question;
use App\Models\StudyDay;
use App\Models\Vocabulary;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('linguapath seeder creates the 60 day path content', function () {
    $this->seed(LinguaPathSeeder::class);

    expect(StudyDay::query()->count())->toBe(60)
        ->and(StudyDay::query()->min('day_number'))->toBe(1)
        ->and(StudyDay::query()->max('day_number'))->toBe(60)
        ->and(Vocabulary::query()->count())->toBeGreaterThanOrEqual(250);

    StudyDay::query()
        ->with('lesson')
        ->get()
        ->each(fn (StudyDay $day) => expect($day->lesson)->not->toBeNull());
});

test('each seeded question has exactly one correct option', function () {
    $this->seed(LinguaPathSeeder::class);

    Question::query()
        ->with('options')
        ->get()
        ->each(fn (Question $question) => expect($question->options->where('is_correct', true)->count())->toBe(1));
});
