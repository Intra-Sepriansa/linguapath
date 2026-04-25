<?php

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\ExamSimulation;
use App\Models\MistakeJournal;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use App\Models\User;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
    approveListeningQuestionsForExam();
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

test('exam selection ignores listening questions without approved real audio', function () {
    $user = User::factory()->create();
    $invalidQuestion = createInvalidListeningQuestionForExam();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->with('answers.question')
        ->firstOrFail();

    expect($simulation->answers->pluck('question_id'))->not->toContain($invalidQuestion->id);
});

test('exam selection ignores reading questions without evidence', function () {
    $user = User::factory()->create();
    $invalidQuestion = createInvalidReadingQuestionForExam();

    $this->actingAs($user)
        ->post(route('exam.start'))
        ->assertRedirect();

    $simulation = ExamSimulation::query()
        ->whereBelongsTo($user)
        ->with('answers.question')
        ->firstOrFail();

    expect($simulation->answers->pluck('question_id'))->not->toContain($invalidQuestion->id);
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

function approveListeningQuestionsForExam(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    Question::query()
        ->where('section_type', SkillType::Listening)
        ->whereIn('status', Question::ACTIVE_STATUSES)
        ->get()
        ->each(function (Question $question) use ($admin): void {
            $transcript = $question->transcript ?: 'Speaker A: Did you finish the review? Speaker B: Yes, I completed it before class.';
            $audio = AudioAsset::query()->create([
                'title' => "Approved listening {$question->id}",
                'audio_url' => "/storage/listening-audio/approved-{$question->id}.mp3",
                'file_path' => null,
                'mime_type' => 'audio/mpeg',
                'file_size' => 1024,
                'uploaded_by' => $admin->id,
                'is_real_audio' => true,
                'playback_limit_exam' => 1,
                'status' => 'ready',
                'transcript' => $transcript,
                'transcript_reviewed_at' => now(),
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'review_notes' => 'Approved for exam simulation tests.',
                'speaker_notes' => 'Approved test audio.',
                'duration_seconds' => 18,
                'accent' => 'american',
                'speed' => 1.0,
                'source' => 'test-approved-audio',
            ]);

            $question->update([
                'audio_asset_id' => $audio->id,
                'audio_url' => $audio->playbackUrl(),
                'transcript' => $transcript,
            ]);
        });
}

function createInvalidListeningQuestionForExam(): Question
{
    $audio = AudioAsset::query()->create([
        'title' => 'Invalid listening audio',
        'audio_url' => '/storage/listening-audio/invalid.mp3',
        'is_real_audio' => false,
        'playback_limit_exam' => 1,
        'status' => 'ready',
        'transcript' => 'Speaker A: Is this audio approved? Speaker B: Not yet.',
        'duration_seconds' => 18,
        'accent' => 'american',
        'speed' => 1.0,
        'source' => 'test-invalid-audio',
    ]);

    $question = Question::factory()->create([
        'section_type' => SkillType::Listening,
        'question_type' => QuestionType::ShortConversation,
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'exam_eligible' => true,
        'audio_asset_id' => $audio->id,
        'question_text' => 'Why is this invalid audio excluded?',
    ]);

    foreach (['A', 'B', 'C', 'D'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_label' => $label,
            'option_text' => "Listening option {$label}",
            'is_correct' => $index === 0,
        ]);
    }

    return $question;
}

function createInvalidReadingQuestionForExam(): Question
{
    $passage = Passage::query()->create([
        'title' => 'Invalid Evidence Passage',
        'topic' => 'Academic reading',
        'body' => collect(range(1, 320))->map(fn (int $index): string => "reading{$index}")->implode(' '),
        'word_count' => 320,
        'difficulty' => 'intermediate',
        'source' => 'test-invalid-reading',
        'status' => 'published',
        'reviewed_at' => now(),
    ]);
    $skillTag = SkillTag::query()->firstOrCreate(
        ['code' => 'invalid-reading-evidence'],
        [
            'name' => 'Invalid Reading Evidence',
            'domain' => 'reading',
            'description' => 'Reading evidence exclusion test.',
            'difficulty' => 'intermediate',
        ]
    );
    $question = Question::factory()->create([
        'section_type' => SkillType::Reading,
        'question_type' => QuestionType::MainIdea,
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'exam_eligible' => true,
        'passage_id' => $passage->id,
        'skill_tag_id' => $skillTag->id,
        'evidence_sentence' => '',
        'question_text' => 'Which reading question should be excluded?',
        'explanation' => 'It lacks a required evidence sentence.',
    ]);

    foreach (['A', 'B', 'C', 'D'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_label' => $label,
            'option_text' => "Reading option {$label}",
            'is_correct' => $index === 0,
        ]);
    }

    return $question;
}
