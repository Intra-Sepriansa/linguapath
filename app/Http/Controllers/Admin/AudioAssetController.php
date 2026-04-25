<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioAssetRequest;
use App\Models\AudioAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AudioAssetController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/audio-assets/index', [
            'assets' => AudioAsset::query()
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (AudioAsset $asset): array => [
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'status' => $asset->status,
                    'is_real_audio' => $asset->is_real_audio,
                    'audio_url' => $asset->playbackUrl(),
                    'duration_seconds' => $asset->duration_seconds,
                    'accent' => $asset->accent,
                    'speed' => $asset->speed,
                    'file_size' => $asset->file_size,
                    'created_at' => $asset->created_at->toDateString(),
                ]),
        ]);
    }

    public function store(StoreAudioAssetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('audio_file');

        DB::transaction(function () use ($request, $data, $file): void {
            $asset = AudioAsset::query()->create([
                'title' => $data['title'],
                'transcript' => $data['transcript'],
                'speaker_notes' => $data['speaker_notes'] ?? null,
                'duration_seconds' => $data['duration_seconds'] ?? 0,
                'accent' => $data['accent'] ?? 'american',
                'speed' => $data['speed'] ?? 1.00,
                'source' => 'admin-upload',
                'uploaded_by' => $request->user()->id,
                'is_real_audio' => true,
                'playback_limit_exam' => $data['playback_limit_exam'] ?? 1,
                'status' => $data['status'] ?? 'ready',
            ]);

            $path = $file->store("listening-audio/{$asset->id}", 'public');

            $asset->update([
                'file_path' => $path,
                'audio_url' => Storage::disk('public')->url($path),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        });

        return back();
    }
}
