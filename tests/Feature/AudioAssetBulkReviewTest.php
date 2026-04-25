<?php

use App\Enums\SkillType;
use App\Models\AudioAsset;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can bulk approve valid audio', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $audio = bulkAudioCreateAsset();

    $this->actingAs($admin)
        ->patch(route('admin.audio-assets.bulk-review'), [
            'asset_ids' => [$audio->id],
            'action' => 'approve_selected',
            'review_notes' => 'Bulk approved.',
        ])
        ->assertRedirect()
        ->assertSessionHas('bulkAudioReview', [
            'processed' => 1,
            'skipped' => 0,
            'errors' => [],
        ]);

    $audio->refresh();

    expect($audio->status)->toBe('ready')
        ->and($audio->transcript_reviewed_at)->not()->toBeNull()
        ->and($audio->approved_at)->not()->toBeNull()
        ->and($audio->approved_by)->toBe($admin->id);
});

test('bulk approve rejects audio missing transcript', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $audio = bulkAudioCreateAsset(['transcript' => '']);

    $this->actingAs($admin)
        ->patch(route('admin.audio-assets.bulk-review'), [
            'asset_ids' => [$audio->id],
            'action' => 'approve_selected',
        ])
        ->assertRedirect()
        ->assertSessionHas('bulkAudioReview', fn (array $result): bool => $result['processed'] === 0
            && $result['skipped'] === 1
            && str_contains($result['errors'][0], 'Transcript is required'));

    expect($audio->fresh()->approved_at)->toBeNull();
});

test('bulk approve rejects audio missing file or url', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $audio = bulkAudioCreateAsset(['audio_url' => null, 'file_path' => null]);

    $this->actingAs($admin)
        ->patch(route('admin.audio-assets.bulk-review'), [
            'asset_ids' => [$audio->id],
            'action' => 'approve_selected',
        ])
        ->assertRedirect()
        ->assertSessionHas('bulkAudioReview', fn (array $result): bool => $result['processed'] === 0
            && $result['skipped'] === 1
            && str_contains($result['errors'][0], 'Audio file or URL is required'));

    expect($audio->fresh()->approved_at)->toBeNull();
});

test('normal user cannot bulk approve audio', function () {
    $user = User::factory()->create(['role' => 'user']);
    $audio = bulkAudioCreateAsset();

    $this->actingAs($user)
        ->patch(route('admin.audio-assets.bulk-review'), [
            'asset_ids' => [$audio->id],
            'action' => 'approve_selected',
        ])
        ->assertForbidden();
});

test('audio index exposes bulk review payload fields and filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $audio = bulkAudioCreateAsset();
    Question::factory()->create([
        'section_type' => SkillType::Listening,
        'audio_asset_id' => $audio->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.audio-assets.index', ['filter' => 'real_audio']))
        ->assertOk();

    $payload = collect($response->inertiaProps('assets'))->firstWhere('id', $audio->id);

    expect($response->inertiaProps('filters.filter'))->toBe('real_audio')
        ->and($payload['questions_count'])->toBe(1)
        ->and($payload['listening_questions_count'])->toBe(1)
        ->and($payload['is_attached_to_listening_question'])->toBeTrue()
        ->and($payload['can_be_approved'])->toBeTrue()
        ->and($payload['approval_blockers'])->toBe([]);
});

function bulkAudioCreateAsset(array $overrides = []): AudioAsset
{
    return AudioAsset::query()->create(array_merge([
        'title' => 'Bulk Review Audio',
        'audio_url' => '/storage/listening-audio/bulk-review.mp3',
        'file_path' => null,
        'mime_type' => 'audio/mpeg',
        'file_size' => 1024,
        'is_real_audio' => true,
        'playback_limit_exam' => 1,
        'status' => 'draft',
        'transcript' => 'Speaker A: Is this ready? Speaker B: It is ready for review.',
        'duration_seconds' => 18,
        'accent' => 'american',
        'speed' => 1.0,
        'source' => 'test',
    ], $overrides));
}
