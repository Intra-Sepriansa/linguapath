<?php

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use App\Services\ExamReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    File::deleteDirectory(storage_path('framework/testing/listening-import'));
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/listening-import'));
});

test('listening audio importer rejects missing audio files', function () {
    Storage::fake('public');
    $question = importerCreateListeningQuestion();
    $manifestPath = importerCreateManifest([
        importerManifestRow($question, ['audio_filename' => 'missing.mp3']),
    ], createAudioFile: false);

    $this->artisan('linguapath:import-listening-audio', ['manifest' => $manifestPath])
        ->assertExitCode(1);

    expect(AudioAsset::query()->count())->toBe(0)
        ->and($question->fresh()->audio_asset_id)->toBeNull();
});

test('listening audio importer rejects non listening questions', function () {
    Storage::fake('public');
    $question = importerCreateListeningQuestion([
        'section_type' => SkillType::Structure,
        'question_type' => QuestionType::IncompleteSentence,
    ]);
    $manifestPath = importerCreateManifest([
        importerManifestRow($question),
    ]);

    $this->artisan('linguapath:import-listening-audio', ['manifest' => $manifestPath])
        ->assertExitCode(1);

    expect(AudioAsset::query()->count())->toBe(0)
        ->and($question->fresh()->audio_asset_id)->toBeNull();
});

test('listening audio importer attaches fifty approved audio assets and reaches listening target', function () {
    Storage::fake('public');
    $questions = collect(range(1, 50))
        ->map(fn (int $index): Question => importerCreateListeningQuestion([
            'question_text' => "Listening import question {$index}",
        ]));
    $manifestPath = importerCreateManifest(
        $questions
            ->map(fn (Question $question, int $index): array => importerManifestRow($question, [
                'audio_filename' => "clip-{$index}.mp3",
                'title' => "Imported audio {$index}",
            ]))
            ->all(),
        audioFilenames: collect(range(0, 49))->map(fn (int $index): string => "clip-{$index}.mp3")->all(),
    );

    $this->artisan('linguapath:import-listening-audio', ['manifest' => $manifestPath])
        ->assertExitCode(0);

    $payload = app(ExamReadinessService::class)->dashboardPayload();

    expect(AudioAsset::query()->count())->toBe(50)
        ->and(Question::query()->whereNotNull('audio_asset_id')->count())->toBe(50)
        ->and($payload['sections']['listening']['raw_ready_count'])->toBe(50)
        ->and($payload['sections']['listening']['capped_ready_count'])->toBe(50)
        ->and($payload['sections']['listening']['ready'])->toBeTrue();
});

function importerCreateListeningQuestion(array $overrides = []): Question
{
    $question = Question::factory()->create(array_merge([
        'lesson_id' => null,
        'section_type' => SkillType::Listening,
        'question_type' => QuestionType::ShortConversation,
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'exam_eligible' => true,
        'skill_tag_id' => importerSkillTag()->id,
        'audio_asset_id' => null,
        'question_text' => 'What does the second speaker imply?',
        'explanation' => 'The second speaker confirms the review is complete.',
    ], $overrides));

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

function importerSkillTag(): SkillTag
{
    return SkillTag::query()->firstOrCreate(
        ['code' => 'importer-listening'],
        [
            'name' => 'Importer Listening',
            'domain' => 'listening',
            'description' => 'Importer test tag.',
            'difficulty' => 'intermediate',
        ]
    );
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  list<string>  $audioFilenames
 */
function importerCreateManifest(array $rows, bool $createAudioFile = true, array $audioFilenames = ['clip.mp3']): string
{
    $basePath = storage_path('framework/testing/listening-import');
    $audioPath = $basePath.'/audio';
    File::ensureDirectoryExists($audioPath);

    if ($createAudioFile) {
        foreach ($audioFilenames as $filename) {
            File::put($audioPath.'/'.$filename, str_repeat('0', 256));
        }
    }

    $manifestPath = $basePath.'/manifest.json';
    File::put($manifestPath, json_encode($rows, JSON_PRETTY_PRINT));

    return $manifestPath;
}

/**
 * @return array<string, mixed>
 */
function importerManifestRow(Question $question, array $overrides = []): array
{
    return array_merge([
        'question_id' => $question->id,
        'audio_filename' => 'clip.mp3',
        'title' => 'Imported Listening Audio',
        'transcript' => 'Speaker A: Is the import complete? Speaker B: Yes, it is ready.',
        'duration_seconds' => 18,
        'source_type' => 'local-demo',
        'speaker_accent' => 'american',
        'is_real_audio' => true,
        'transcript_reviewed' => true,
        'quality_status' => 'approved',
        'approved_at' => now()->toIso8601String(),
    ], $overrides);
}
