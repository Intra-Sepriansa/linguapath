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
        ->and($asset->file_path)->not()->toBeNull();

    Storage::disk('public')->assertExists($asset->file_path);
});
