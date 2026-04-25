<?php

use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Enums\VocabularyStatus;
use App\Models\MistakeJournal;
use App\Models\PracticeSession;
use App\Models\StudyDay;
use App\Models\StudyLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserVocabulary;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->seed(LinguaPathSeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard exposes advanced learning command center metrics', function () {
    $this->seed(LinguaPathSeeder::class);

    $user = User::factory()->create();
    $days = StudyDay::query()
        ->orderBy('day_number')
        ->take(4)
        ->get()
        ->values();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'target_score' => 560,
        'daily_goal_minutes' => 60,
    ]);

    StudyLog::factory()->create([
        'user_id' => $user->id,
        'study_day_id' => $days[0]->id,
        'minutes_spent' => 55,
        'completed_lessons' => 1,
        'completed_questions' => 20,
        'accuracy' => 82,
        'log_date' => today()->subDay(),
    ]);

    StudyLog::factory()->create([
        'user_id' => $user->id,
        'study_day_id' => $days[1]->id,
        'minutes_spent' => 45,
        'completed_lessons' => 1,
        'completed_questions' => 15,
        'accuracy' => 78,
        'log_date' => today(),
    ]);

    PracticeSession::factory()->create([
        'user_id' => $user->id,
        'study_day_id' => $days[0]->id,
        'section_type' => SkillType::Structure,
        'total_questions' => 20,
        'score' => 72,
        'duration_seconds' => 840,
        'finished_at' => now()->subDays(3),
    ]);

    PracticeSession::factory()->create([
        'user_id' => $user->id,
        'study_day_id' => $days[1]->id,
        'section_type' => SkillType::Listening,
        'total_questions' => 20,
        'score' => 58,
        'duration_seconds' => 900,
        'finished_at' => now()->subDays(2),
    ]);

    PracticeSession::factory()->create([
        'user_id' => $user->id,
        'study_day_id' => $days[2]->id,
        'section_type' => SkillType::Reading,
        'total_questions' => 20,
        'score' => 81,
        'duration_seconds' => 960,
        'finished_at' => now()->subDay(),
    ]);

    MistakeJournal::factory()->create([
        'user_id' => $user->id,
        'section_type' => SkillType::Structure,
        'review_status' => ReviewStatus::New,
    ]);

    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'status' => VocabularyStatus::Weak,
    ]);

    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'status' => VocabularyStatus::Mastered,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('overview.profile.target_score', 560)
            ->where('overview.path.completed_days', 2)
            ->where('overview.path.completion_percentage', 3)
            ->where('overview.study_load.weekly_minutes', 100)
            ->where('overview.study_load.weekly_questions', 35)
            ->where('overview.study_load.average_accuracy', 80)
            ->where('overview.mistakes_to_review', 1)
            ->where('overview.vocabulary.weak', 1)
            ->has('overview.focus_queue', 4)
            ->has('overview.skill_diagnostics', 3)
            ->has('overview.upcoming_days', 4)
            ->has('overview.recent_sessions', 3)
            ->has('overview.mistake_heatmap', 5));
});
