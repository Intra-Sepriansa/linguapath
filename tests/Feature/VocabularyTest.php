<?php

use App\Models\User;
use App\Models\UserVocabulary;
use App\Models\Vocabulary;
use App\Services\VocabularyService;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('users can view daily vocabulary', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('vocabulary.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vocabulary/index')
            ->has('words', 72)
            ->has('words.0.quiz_options', 4)
            ->has('words.0.status_label')
            ->has('words.0.review_count')
            ->has('words.0.pronunciation_text')
            ->has('words.0.pronunciation_lookup_terms')
            ->has('words.0.pronunciation_locale')
            ->has('words.0.usage_note')
            ->has('words.0.example_translation')
            ->has('summary.categories')
            ->has('summary.difficulties'));

    expect($response->inertiaProps('summary.total'))->toBeGreaterThanOrEqual(250)
        ->and($response->inertiaProps('summary.available'))->toBeGreaterThanOrEqual(72)
        ->and($response->inertiaProps('words.0.pronunciation_text'))->toBeString()->not->toBeEmpty()
        ->and($response->inertiaProps('words.0.pronunciation_lookup_terms'))->toBeArray()->not->toBeEmpty()
        ->and($response->inertiaProps('words.0.pronunciation_locale'))->toBe('en-US')
        ->and($response->inertiaProps('words.0.usage_note'))->toBeString()->not->toBeEmpty()
        ->and($response->inertiaProps('words.0.example_translation'))->toBeString()->not->toBeEmpty();
});

test('daily vocabulary includes clear english pronunciation text', function () {
    $user = User::factory()->create();

    Vocabulary::query()->delete();

    Vocabulary::factory()->create([
        'word' => 'vocabulary-in-context',
        'meaning' => 'kosakata dalam konteks',
    ]);

    $words = app(VocabularyService::class)->daily($user, 1);

    expect($words)->toHaveCount(1)
        ->and($words[0]['pronunciation_text'])->toBe('vocabulary in context')
        ->and($words[0]['pronunciation_lookup_terms'])->toBe([
            'vocabulary-in-context',
            'vocabulary in context',
        ])
        ->and($words[0]['pronunciation_locale'])->toBe('en-US');
});

test('users can mark vocabulary status', function () {
    $user = User::factory()->create();
    $vocabulary = Vocabulary::query()->firstOrFail();

    $this->actingAs($user)
        ->patch(route('vocabulary.mark', $vocabulary), ['status' => 'mastered'])
        ->assertRedirect();

    expect(UserVocabulary::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($vocabulary)
        ->where('status', 'mastered')
        ->exists())->toBeTrue();
});
