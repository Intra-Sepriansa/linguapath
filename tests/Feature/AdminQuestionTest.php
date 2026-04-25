<?php

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Passage;
use App\Models\PracticeAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('admin can view questions index but normal users cannot', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.questions.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.questions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/questions/index')
            ->has('questions.data')
            ->has('filters')
            ->has('options.questionTypes')
            ->has('stats'));
});

test('admin can create a ready reading question with four options and one correct answer', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $passage = createAdminQuestionPassage();
    $skillTag = createAdminQuestionSkillTag('reading');

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), validQuestionPayload([
            'section_type' => SkillType::Reading->value,
            'question_type' => QuestionType::MainIdea->value,
            'passage_id' => $passage->id,
            'skill_tag_id' => $skillTag->id,
            'evidence_sentence' => 'The passage explains that immediate review keeps the original reasoning fresh.',
        ]))
        ->assertRedirect();

    $question = Question::query()
        ->where('question_text', 'What is the main idea of the passage?')
        ->with('options')
        ->firstOrFail();

    expect($question->section_type)->toBe(SkillType::Reading)
        ->and($question->passage_id)->toBe($passage->id)
        ->and($question->status)->toBe('ready')
        ->and($question->exam_eligible)->toBeTrue()
        ->and($question->options)->toHaveCount(4)
        ->and($question->options->where('is_correct', true))->toHaveCount(1);
});

test('admin can create structure and listening questions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $structureTag = createAdminQuestionSkillTag('structure');
    $listeningTag = createAdminQuestionSkillTag('listening');
    $audio = createAdminQuestionAudio();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), validQuestionPayload([
            'section_type' => SkillType::Structure->value,
            'question_type' => QuestionType::IncompleteSentence->value,
            'skill_tag_id' => $structureTag->id,
            'passage_id' => null,
            'audio_asset_id' => null,
            'evidence_sentence' => null,
            'question_text' => 'The student ___ the grammar rule before practice.',
        ]))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), validQuestionPayload([
            'section_type' => SkillType::Listening->value,
            'question_type' => QuestionType::ShortConversation->value,
            'skill_tag_id' => $listeningTag->id,
            'audio_asset_id' => $audio->id,
            'passage_id' => null,
            'evidence_sentence' => null,
            'question_text' => 'What does the second speaker imply?',
        ]))
        ->assertRedirect();

    expect(Question::query()->where('section_type', SkillType::Structure)->count())->toBe(1)
        ->and(Question::query()->where('section_type', SkillType::Listening)->where('audio_asset_id', $audio->id)->count())->toBe(1);
});

test('question option validation requires exactly four unique labels and one correct answer', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $payload = validQuestionPayload([
        'section_type' => SkillType::Structure->value,
        'question_type' => QuestionType::IncompleteSentence->value,
        'skill_tag_id' => createAdminQuestionSkillTag('structure')->id,
        'passage_id' => null,
        'audio_asset_id' => null,
        'evidence_sentence' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            ...$payload,
            'options' => array_slice($payload['options'], 0, 3),
        ])
        ->assertSessionHasErrors('options');

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            ...$payload,
            'options' => [
                ['label' => 'A', 'text' => 'One', 'is_correct' => false],
                ['label' => 'A', 'text' => 'Duplicate', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Three', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Four', 'is_correct' => true],
            ],
        ])
        ->assertSessionHasErrors('options');

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            ...$payload,
            'options' => collect($payload['options'])
                ->map(fn (array $option): array => [...$option, 'is_correct' => false])
                ->all(),
        ])
        ->assertSessionHasErrors('options');

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            ...$payload,
            'options' => collect($payload['options'])
                ->map(fn (array $option, int $index): array => [...$option, 'is_correct' => $index < 2])
                ->all(),
        ])
        ->assertSessionHasErrors('options');
});

test('ready published validation requires required learning metadata', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $passage = createAdminQuestionPassage();
    $payload = validQuestionPayload([
        'status' => 'published',
        'section_type' => SkillType::Reading->value,
        'question_type' => '',
        'difficulty' => '',
        'explanation' => '',
        'skill_tag_id' => '',
        'passage_id' => '',
        'evidence_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), $payload)
        ->assertSessionHasErrors([
            'question_type',
            'difficulty',
            'explanation',
            'skill_tag_id',
            'passage_id',
            'evidence_sentence',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), validQuestionPayload([
            'section_type' => SkillType::Reading->value,
            'passage_id' => $passage->id,
            'skill_tag_id' => createAdminQuestionSkillTag('reading')->id,
            'evidence_sentence' => '',
        ]))
        ->assertSessionHasErrors('evidence_sentence');
});

test('ready listening question requires an audio asset', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), validQuestionPayload([
            'section_type' => SkillType::Listening->value,
            'question_type' => QuestionType::ShortConversation->value,
            'skill_tag_id' => createAdminQuestionSkillTag('listening')->id,
            'passage_id' => null,
            'audio_asset_id' => null,
            'evidence_sentence' => null,
        ]))
        ->assertSessionHasErrors('audio_asset_id');
});

test('admin can update a question and options without duplicating options', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $question = createQuestionWithOptions();
    $payload = validQuestionPayload([
        'section_type' => SkillType::Structure->value,
        'question_type' => QuestionType::ErrorRecognition->value,
        'skill_tag_id' => createAdminQuestionSkillTag('structure')->id,
        'passage_id' => null,
        'audio_asset_id' => null,
        'evidence_sentence' => null,
        'question_text' => 'Identify the part that must be changed in the sentence.',
        'options' => [
            ['label' => 'A', 'text' => 'Identify', 'is_correct' => false],
            ['label' => 'B', 'text' => 'must be changed', 'is_correct' => true],
            ['label' => 'C', 'text' => 'in the', 'is_correct' => false],
            ['label' => 'D', 'text' => 'sentence', 'is_correct' => false],
        ],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.questions.update', $question), $payload)
        ->assertRedirect(route('admin.questions.show', $question));

    $question->refresh();

    expect($question->options()->count())->toBe(4)
        ->and($question->options()->where('is_correct', true)->count())->toBe(1)
        ->and($question->options()->where('option_label', 'B')->first()->option_text)->toBe('must be changed');
});

test('admin archives used questions and deletes unused questions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $used = createQuestionWithOptions();
    $unused = createQuestionWithOptions(['question_text' => 'The unused question has no learner history.']);

    PracticeAnswer::factory()->create(['question_id' => $used->id]);

    $this->actingAs($admin)
        ->delete(route('admin.questions.destroy', $used))
        ->assertRedirect();

    $used->refresh();

    expect($used->status)->toBe('archived')
        ->and($used->exam_eligible)->toBeFalse()
        ->and(Question::query()->whereKey($used->id)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.questions.destroy', $unused))
        ->assertRedirect(route('admin.questions.index'));

    expect(Question::query()->whereKey($unused->id)->exists())->toBeFalse()
        ->and(QuestionOption::query()->where('question_id', $unused->id)->count())->toBe(0);
});

test('quality warnings are computed and exposed on preview', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $question = Question::factory()->create([
        'section_type' => SkillType::Reading,
        'question_type' => null,
        'difficulty' => null,
        'status' => 'ready',
        'explanation' => null,
        'passage_id' => null,
        'skill_tag_id' => null,
        'evidence_sentence' => null,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'option_label' => 'A',
        'is_correct' => false,
    ]);

    expect($question->qualityWarnings())->toContain('Missing explanation')
        ->and($question->qualityWarnings())->toContain('Reading question without passage')
        ->and($question->qualityWarnings())->toContain('No correct answer');

    $this->actingAs($admin)
        ->get(route('admin.questions.show', $question))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/questions/show')
            ->where('question.id', $question->id)
            ->where('question.quality_warnings', fn ($warnings): bool => collect($warnings)->contains('Missing explanation')));
});

function validQuestionPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'section_type' => SkillType::Reading->value,
        'question_type' => QuestionType::MainIdea->value,
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'passage_id' => createAdminQuestionPassage()->id,
        'audio_asset_id' => null,
        'skill_tag_id' => createAdminQuestionSkillTag('reading')->id,
        'question_text' => 'What is the main idea of the passage?',
        'explanation' => 'The passage focuses on immediate review and better learning efficiency.',
        'evidence_sentence' => 'Immediate review keeps the original reasoning fresh.',
        'options' => [
            ['label' => 'A', 'text' => 'Immediate review improves practice efficiency.', 'is_correct' => true],
            ['label' => 'B', 'text' => 'Students should avoid reading passages.', 'is_correct' => false],
            ['label' => 'C', 'text' => 'Audio practice is always unnecessary.', 'is_correct' => false],
            ['label' => 'D', 'text' => 'Grammar rules never affect test scores.', 'is_correct' => false],
        ],
    ], $overrides);
}

function createQuestionWithOptions(array $overrides = []): Question
{
    $question = Question::factory()->create(array_merge([
        'section_type' => SkillType::Structure,
        'question_type' => QuestionType::IncompleteSentence,
        'difficulty' => 'intermediate',
        'status' => 'ready',
        'skill_tag_id' => createAdminQuestionSkillTag('structure')->id,
    ], $overrides));

    foreach (['A', 'B', 'C', 'D'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_label' => $label,
            'option_text' => "Option {$label}",
            'is_correct' => $index === 0,
        ]);
    }

    return $question;
}

function createAdminQuestionPassage(): Passage
{
    return Passage::query()->create([
        'title' => 'Immediate Review in Academic Reading',
        'topic' => 'Learning science',
        'body' => collect(range(1, 320))->map(fn (int $index): string => "academic{$index}")->implode(' '),
        'word_count' => 320,
        'difficulty' => 'intermediate',
        'source' => 'test',
        'status' => 'published',
        'reviewed_at' => now(),
    ]);
}

function createAdminQuestionAudio(): AudioAsset
{
    return AudioAsset::query()->create([
        'title' => 'Short Conversation',
        'audio_url' => '/storage/listening-audio/test.mp3',
        'file_path' => null,
        'mime_type' => 'audio/mpeg',
        'file_size' => 1024,
        'uploaded_by' => User::factory()->create(['role' => 'admin'])->id,
        'is_real_audio' => true,
        'playback_limit_exam' => 1,
        'status' => 'ready',
        'transcript' => 'Speaker A: Are you ready? Speaker B: I finished the review.',
        'speaker_notes' => 'Short conversation.',
        'duration_seconds' => 18,
        'accent' => 'american',
        'speed' => 1.0,
        'source' => 'test',
    ]);
}

function createAdminQuestionSkillTag(string $domain): SkillTag
{
    return SkillTag::query()->firstOrCreate(
        ['code' => "{$domain}-admin-test"],
        [
            'name' => ucfirst($domain).' Admin Test',
            'domain' => $domain,
            'description' => 'Admin question test tag.',
            'difficulty' => 'intermediate',
        ]
    );
}
