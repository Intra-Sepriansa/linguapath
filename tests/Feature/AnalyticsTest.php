<?php

use App\Enums\MistakeType;
use App\Enums\SkillType;
use App\Enums\VocabularyStatus;
use App\Models\MistakeJournal;
use App\Models\PracticeSession;
use App\Models\StudyLog;
use App\Models\User;
use App\Models\UserVocabulary;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('authenticated users can view analytics', function () {
    $this->seed(LinguaPathSeeder::class);

    $user = User::factory()->create();

    StudyLog::factory()->for($user)->create([
        'minutes_spent' => 35,
        'completed_questions' => 18,
        'accuracy' => 72,
        'log_date' => today()->subDays(2),
    ]);
    StudyLog::factory()->for($user)->create([
        'minutes_spent' => 45,
        'completed_questions' => 24,
        'accuracy' => 84,
        'log_date' => today(),
    ]);

    PracticeSession::factory()->for($user)->create([
        'section_type' => SkillType::Listening,
        'total_questions' => 20,
        'correct_answers' => 15,
        'score' => 75,
        'duration_seconds' => 1200,
        'finished_at' => now()->subDays(4),
    ]);
    PracticeSession::factory()->for($user)->create([
        'section_type' => SkillType::Structure,
        'total_questions' => 20,
        'correct_answers' => 13,
        'score' => 65,
        'duration_seconds' => 1320,
        'finished_at' => now()->subDays(3),
    ]);
    PracticeSession::factory()->for($user)->create([
        'section_type' => SkillType::Reading,
        'total_questions' => 20,
        'correct_answers' => 17,
        'score' => 85,
        'duration_seconds' => 1500,
        'finished_at' => now()->subDay(),
    ]);

    MistakeJournal::factory()->for($user)->create([
        'section_type' => SkillType::Structure,
        'mistake_type' => MistakeType::Grammar,
    ]);
    UserVocabulary::factory()->for($user)->create([
        'status' => VocabularyStatus::Mastered,
        'review_count' => 4,
        'last_reviewed_at' => now()->subDays(2),
    ]);
    UserVocabulary::factory()->for($user)->create([
        'status' => VocabularyStatus::Weak,
        'review_count' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('analytics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('analytics/index')
            ->has('analytics.readiness')
            ->has('analytics.summary')
            ->has('analytics.projection')
            ->has('analytics.activity', 30)
            ->has('analytics.skill_breakdown', 3)
            ->has('analytics.mistake_types', 1)
            ->has('analytics.mistake_sections', 5)
            ->has('analytics.vocabulary')
            ->has('analytics.recommendations', 4)
            ->where('analytics.summary.total_minutes', 80)
            ->where('analytics.summary.total_questions', 42)
            ->where('analytics.vocabulary.review_ready', 1));
});
