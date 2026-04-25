<?php

use App\Enums\SkillType;
use App\Models\Passage;
use App\Models\Question;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('toefl reading import provides enough long passages and questions for an exam section', function () {
    $longPassages = Passage::query()
        ->where('source', 'toefl_core_passages.json')
        ->whereBetween('word_count', [300, 700])
        ->count();

    $longPassageQuestions = Question::query()
        ->where('section_type', SkillType::Reading)
        ->where('exam_eligible', true)
        ->whereHas('passage', fn ($query) => $query->where('word_count', '>=', 300))
        ->count();

    expect($longPassages)->toBeGreaterThanOrEqual(5)
        ->and($longPassageQuestions)->toBeGreaterThanOrEqual(50);
});

test('exam eligible reading questions include evidence sentence skill tag and one correct option', function () {
    Question::query()
        ->where('section_type', SkillType::Reading)
        ->where('exam_eligible', true)
        ->whereHas('passage', fn ($query) => $query->where('word_count', '>=', 300))
        ->with('options')
        ->get()
        ->each(function (Question $question): void {
            expect($question->evidence_sentence)->not()->toBeEmpty()
                ->and($question->skill_tag_id)->not()->toBeNull()
                ->and($question->difficulty)->not()->toBeEmpty()
                ->and($question->options)->toHaveCount(4)
                ->and($question->options->where('is_correct', true))->toHaveCount(1);
        });
});
