<?php

use App\Models\AudioAsset;
use App\Models\User;
use Database\Seeders\LinguaPathSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LinguaPathSeeder::class);
});

test('admin can access content dashboard but normal users cannot', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('metrics.lessons')
            ->has('metrics.missing_audio')
            ->has('metrics.short_passages')
            ->has('examReadiness.sections.listening.ready_count')
            ->has('examReadiness.sections.listening.raw_ready_count')
            ->has('examReadiness.sections.listening.capped_ready_count')
            ->has('examReadiness.sections.structure.ready_count')
            ->has('examReadiness.sections.reading.ready_count')
            ->has('examReadiness.total_capped_ready')
            ->has('examReadiness.total_raw_ready')
            ->has('examReadiness.primary_blocker_message')
            ->has('examReadiness.full_exam_ready')
            ->has('examReadiness.issues.listening_missing_audio')
            ->has('examReadiness.issues.audio_not_real')
            ->has('examReadiness.issues.transcript_not_reviewed')
            ->has('examReadiness.issues.audio_not_approved')
            ->has('examReadiness.issues.reading_missing_evidence')
            ->has('examReadiness.issues.invalid_options')
            ->has('qualityFlags.question_distribution'));
});

test('admin can upload a real listening audio asset', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.audio-assets.store'), [
            'title' => 'Short Conversation Audio',
            'audio_file' => UploadedFile::fake()->create('conversation.mp3', 512, 'audio/mpeg'),
            'transcript' => 'Speaker A: Could you review the notes? Speaker B: I already did.',
            'duration_seconds' => 18,
            'accent' => 'american',
            'playback_limit_exam' => 1,
            'status' => 'ready',
        ])
        ->assertRedirect();

    $asset = AudioAsset::query()
        ->where('title', 'Short Conversation Audio')
        ->firstOrFail();

    expect($asset->is_real_audio)->toBeTrue()
        ->and($asset->uploaded_by)->toBe($admin->id)
        ->and($asset->file_path)->not()->toBeNull()
        ->and($asset->approved_at)->toBeNull()
        ->and($asset->transcript_reviewed_at)->toBeNull();

    Storage::disk('public')->assertExists($asset->file_path);

    $response = $this->actingAs($admin)
        ->get(route('admin.audio-assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/audio-assets/index')
            ->has('assets'));

    $assetPayload = collect($response->inertiaProps('assets'))
        ->firstWhere('id', $asset->id);

    expect($assetPayload['is_approved_for_exam'])->toBeFalse()
        ->and($assetPayload['can_be_approved'])->toBeTrue()
        ->and($assetPayload['questions_count'])->toBe(0)
        ->and($assetPayload['quality_badges'])->toContain('Real Audio')
        ->and($assetPayload['quality_badges'])->toContain('Needs Review');

    $this->actingAs($admin)
        ->patch(route('admin.audio-assets.review', $asset), [
            'transcript_reviewed' => true,
            'approved' => true,
            'status' => 'ready',
            'review_notes' => 'Transcript reviewed and approved.',
        ])
        ->assertRedirect();

    $asset->refresh();

    expect($asset->isApprovedForExam())->toBeTrue()
        ->and($asset->qualityBadges())->toContain('Transcript Reviewed')
        ->and($asset->qualityBadges())->toContain('Approved');
});
