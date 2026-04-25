<?php

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use App\Models\User;
use App\Services\ExamReadinessService;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeded transcript fallback listening questions are not exam ready', function () {
    $this->seed(LinguaPathSeeder::class);

    $payload = app(ExamReadinessService::class)->dashboardPayload();

    expect(Question::query()->where('section_type', SkillType::Listening)->count())->toBeGreaterThan(0)
        ->and($payload['sections']['listening']['ready_count'])->toBe(0)
        ->and($payload['full_exam_ready'])->toBeFalse();
});

test('approved real audio listening questions count as ready', function () {
    $audio = readinessCreateAudio();
    readinessCreateQuestion(SkillType::Listening, [
        'audio_asset_id' => $audio->id,
        'question_type' => QuestionType::ShortConversation,
    ]);

    $sections = app(ExamReadinessService::class)->sectionReadiness();

    expect($sections['listening']['ready_count'])->toBe(1)
        ->and($sections['listening']['target'])->toBe(50)
        ->and($sections['listening']['ready'])->toBeFalse();
});

test('invalid listening audio is excluded from readiness counts', function () {
    readinessCreateQuestion(SkillType::Listening, [
        'audio_asset_id' => readinessCreateAudio(['is_real_audio' => false])->id,
        'question_type' => QuestionType::ShortConversation,
    ]);
    readinessCreateQuestion(SkillType::Listening, [
        'audio_asset_id' => readinessCreateAudio(['approved_at' => null, 'approved_by' => null])->id,
        'question_type' => QuestionType::ShortConversation,
    ]);
    readinessCreateQuestion(SkillType::Listening, [
        'audio_asset_id' => readinessCreateAudio(['transcript_reviewed_at' => null])->id,
        'question_type' => QuestionType::ShortConversation,
    ]);

    $service = app(ExamReadinessService::class);
    $issues = $service->issueSummary();

    expect($service->sectionReadiness()['listening']['ready_count'])->toBe(0)
        ->and($issues['audio_not_real']['count'])->toBe(1)
        ->and($issues['audio_not_approved']['count'])->toBe(1)
        ->and($issues['transcript_not_reviewed']['count'])->toBe(1);
});

test('structure readiness uses active exam eligible structure questions', function () {
    readinessCreateQuestion(SkillType::Structure, [
        'question_type' => QuestionType::IncompleteSentence,
    ]);
    readinessCreateQuestion(SkillType::Structure, [
        'question_type' => QuestionType::IncompleteSentence,
        'status' => 'draft',
    ]);
    readinessCreateQuestion(SkillType::Structure, [
        'question_type' => QuestionType::IncompleteSentence,
        'exam_eligible' => false,
    ]);

    expect(app(ExamReadinessService::class)->sectionReadiness()['structure']['ready_count'])->toBe(1);
});

test('reading readiness requires valid passage word count and status', function () {
    $validPassage = readinessCreatePassage(['word_count' => 320, 'status' => 'published']);
    $shortPassage = readinessCreatePassage(['word_count' => 250, 'status' => 'published']);
    $draftPassage = readinessCreatePassage(['word_count' => 320, 'status' => 'draft']);

    readinessCreateQuestion(SkillType::Reading, [
        'question_type' => QuestionType::MainIdea,
        'passage_id' => $validPassage->id,
    ]);
    readinessCreateQuestion(SkillType::Reading, [
        'question_type' => QuestionType::MainIdea,
        'passage_id' => $shortPassage->id,
    ]);
    readinessCreateQuestion(SkillType::Reading, [
        'question_type' => QuestionType::MainIdea,
        'passage_id' => $draftPassage->id,
    ]);

    expect(app(ExamReadinessService::class)->sectionReadiness()['reading']['ready_count'])->toBe(1);
});

test('dashboard payload reports blocked full exam and option issues', function () {
    readinessCreateQuestion(SkillType::Structure, [
        'question_type' => QuestionType::IncompleteSentence,
    ], withOptions: false);

    $payload = app(ExamReadinessService::class)->dashboardPayload();

    expect($payload['full_exam_ready'])->toBeFalse()
        ->and($payload['total_target'])->toBe(140)
        ->and($payload['issues']['invalid_options']['count'])->toBe(1);
});

test('dashboard caps full exam progress while exposing raw ready pools', function () {
    readinessCreateReadyPool(SkillType::Listening, 51);
    readinessCreateReadyPool(SkillType::Structure, 41);
    readinessCreateReadyPool(SkillType::Reading, 51);

    $payload = app(ExamReadinessService::class)->dashboardPayload();

    expect($payload['full_exam_ready'])->toBeTrue()
        ->and($payload['total_raw_ready'])->toBe(143)
        ->and($payload['total_capped_ready'])->toBe(140)
        ->and($payload['total_ready'])->toBe(140)
        ->and($payload['sections']['listening']['raw_ready_count'])->toBe(51)
        ->and($payload['sections']['listening']['capped_ready_count'])->toBe(50)
        ->and($payload['sections']['structure']['capped_ready_count'])->toBe(40)
        ->and($payload['sections']['reading']['capped_ready_count'])->toBe(50)
        ->and($payload['primary_blocker_message'])->toBeNull();
});

test('dashboard is blocked with a clear message when listening is below target', function () {
    readinessCreateReadyPool(SkillType::Structure, 40);
    readinessCreateReadyPool(SkillType::Reading, 50);

    $payload = app(ExamReadinessService::class)->dashboardPayload();

    expect($payload['full_exam_ready'])->toBeFalse()
        ->and($payload['total_capped_ready'])->toBe(90)
        ->and($payload['sections']['listening']['raw_ready_count'])->toBe(0)
        ->and($payload['primary_blocker_message'])->toBe('Blocked: Listening needs 50 exam-ready questions, currently 0.');
});

test('reading questions without evidence are excluded from readiness', function () {
    $passage = readinessCreatePassage();
    $question = readinessCreateQuestion(SkillType::Reading, [
        'question_type' => QuestionType::MainIdea,
        'passage_id' => $passage->id,
        'evidence_sentence' => '',
    ]);

    $service = app(ExamReadinessService::class);

    expect($service->isReadingExamReady($question))->toBeFalse()
        ->and($service->sectionReadiness()['reading']['ready_count'])->toBe(0);
});

function readinessCreateQuestion(SkillType $section, array $overrides = [], bool $withOptions = true): Question
{
    $passageId = $section === SkillType::Reading
        ? readinessCreatePassage()->id
        : null;
    $audioAssetId = $section === SkillType::Listening
        ? readinessCreateAudio()->id
        : null;

    $question = Question::factory()->create(array_merge([
        'lesson_id' => null,
        'section_type' => $section,
        'question_type' => match ($section) {
            SkillType::Listening => QuestionType::ShortConversation,
            SkillType::Reading => QuestionType::MainIdea,
            default => QuestionType::IncompleteSentence,
        },
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'exam_eligible' => true,
        'skill_tag_id' => readinessCreateSkillTag($section)->id,
        'passage_id' => $passageId,
        'audio_asset_id' => $audioAssetId,
        'question_text' => 'What should the student choose for this item?',
        'explanation' => 'This item has enough metadata for readiness counting.',
        'evidence_sentence' => $section === SkillType::Reading
            ? 'The passage directly supports this answer.'
            : null,
    ], $overrides));

    if ($withOptions) {
        readinessCreateOptions($question);
    }

    return $question;
}

function readinessCreateReadyPool(SkillType $section, int $count): void
{
    foreach (range(1, $count) as $index) {
        readinessCreateQuestion($section, [
            'question_text' => "{$section->value} readiness question {$index}",
        ]);
    }
}

function readinessCreateOptions(Question $question): void
{
    foreach (['A', 'B', 'C', 'D'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_label' => $label,
            'option_text' => "Readiness option {$label}",
            'is_correct' => $index === 0,
        ]);
    }
}

function readinessCreateAudio(array $overrides = []): AudioAsset
{
    $admin = User::factory()->create(['role' => 'admin']);

    return AudioAsset::query()->create(array_merge([
        'title' => 'Approved readiness audio',
        'audio_url' => '/storage/listening-audio/readiness.mp3',
        'file_path' => null,
        'mime_type' => 'audio/mpeg',
        'file_size' => 1024,
        'uploaded_by' => $admin->id,
        'is_real_audio' => true,
        'playback_limit_exam' => 1,
        'status' => 'ready',
        'transcript' => 'Speaker A: Is the audio ready? Speaker B: Yes, it has been reviewed.',
        'transcript_reviewed_at' => now(),
        'approved_at' => now(),
        'approved_by' => $admin->id,
        'review_notes' => 'Ready for readiness test.',
        'duration_seconds' => 18,
        'accent' => 'american',
        'speed' => 1.0,
        'source' => 'test',
    ], $overrides));
}

function readinessCreatePassage(array $overrides = []): Passage
{
    $wordCount = $overrides['word_count'] ?? 320;

    return Passage::query()->create(array_merge([
        'title' => 'Readiness Passage',
        'topic' => 'Academic practice',
        'body' => collect(range(1, $wordCount))->map(fn (int $index): string => "term{$index}")->implode(' '),
        'word_count' => $wordCount,
        'difficulty' => 'intermediate',
        'source' => 'test',
        'status' => 'published',
        'reviewed_at' => now(),
    ], $overrides));
}

function readinessCreateSkillTag(SkillType $section): SkillTag
{
    return SkillTag::query()->firstOrCreate(
        ['code' => "readiness-{$section->value}"],
        [
            'name' => "Readiness {$section->label()}",
            'domain' => $section->value,
            'description' => 'Readiness service test tag.',
            'difficulty' => 'intermediate',
        ]
    );
}
