<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkReviewAudioAssetsRequest;
use App\Http\Requests\Admin\ReviewAudioAssetRequest;
use App\Http\Requests\Admin\StoreAudioAssetRequest;
use App\Models\AudioAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AudioAssetController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        return Inertia::render('admin/audio-assets/index', [
            'filters' => [
                'filter' => $filter,
                'options' => [
                    ['value' => '', 'label' => 'All audio'],
                    ['value' => 'needs_review', 'label' => 'Needs Review'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'real_audio', 'label' => 'Real Audio'],
                    ['value' => 'transcript_only', 'label' => 'Transcript Only'],
                    ['value' => 'missing_transcript', 'label' => 'Missing Transcript'],
                    ['value' => 'not_attached', 'label' => 'Not Attached to Question'],
                    ['value' => 'used_by_listening', 'label' => 'Used by Listening Question'],
                ],
            ],
            'assets' => AudioAsset::query()
                ->withCount([
                    'questions',
                    'questions as listening_questions_count' => fn (Builder $query) => $query->where('section_type', SkillType::Listening),
                ])
                ->when($filter !== '', fn (Builder $query) => $this->applyFilter($query, $filter))
                ->latest('id')
                ->limit(100)
                ->get()
                ->map(fn (AudioAsset $asset): array => $this->audioPayload($asset)),
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

    public function review(ReviewAudioAssetRequest $request, AudioAsset $audioAsset): RedirectResponse
    {
        $data = $request->validated();

        if ($data['approved'] && ! $audioAsset->is_real_audio) {
            throw ValidationException::withMessages([
                'approved' => 'Only real uploaded audio can be approved for exam selection.',
            ]);
        }

        if ($data['approved'] && blank($audioAsset->transcript)) {
            throw ValidationException::withMessages([
                'approved' => 'Audio must have a transcript before approval.',
            ]);
        }

        if ($data['approved'] && blank($audioAsset->file_path) && blank($audioAsset->audio_url)) {
            throw ValidationException::withMessages([
                'approved' => 'Audio must have a file or URL before approval.',
            ]);
        }

        $transcriptReviewed = (bool) $data['transcript_reviewed'];
        $approved = (bool) $data['approved'] && $transcriptReviewed;

        $audioAsset->update([
            'status' => $approved ? 'ready' : $data['status'],
            'transcript_reviewed_at' => $transcriptReviewed
                ? ($audioAsset->transcript_reviewed_at ?? now())
                : null,
            'approved_at' => $approved ? ($audioAsset->approved_at ?? now()) : null,
            'approved_by' => $approved ? $request->user()->id : null,
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', $approved ? 'Audio approved for exam selection.' : 'Audio marked as needing review.');
    }

    public function bulkReview(BulkReviewAudioAssetsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $assets = AudioAsset::query()
            ->whereIn('id', $data['asset_ids'])
            ->get();
        $result = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($request, $data, $assets, &$result): void {
            foreach ($assets as $asset) {
                $blockers = $this->bulkActionBlockers($asset, $data['action']);

                if ($blockers !== []) {
                    $result['skipped']++;
                    $result['errors'][] = "{$asset->title}: ".implode(', ', $blockers);

                    continue;
                }

                $updates = $this->bulkActionUpdates($asset, $data['action'], $request->user()->id, $data['review_notes'] ?? null);

                if ($updates !== []) {
                    $asset->update($updates);
                    $result['processed']++;
                }
            }
        });

        return back()->with('bulkAudioReview', $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function audioPayload(AudioAsset $asset): array
    {
        $approvalBlockers = $this->approvalBlockers($asset);

        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'status' => $asset->status,
            'source' => $asset->source,
            'is_real_audio' => $asset->is_real_audio,
            'is_approved_for_exam' => $asset->isApprovedForExam(),
            'can_be_approved' => $approvalBlockers === [],
            'approval_blockers' => $approvalBlockers,
            'quality_badges' => $asset->qualityBadges(),
            'audio_url' => $asset->playbackUrl(),
            'duration_seconds' => $asset->duration_seconds,
            'accent' => $asset->accent,
            'speed' => $asset->speed,
            'file_size' => $asset->file_size,
            'transcript_reviewed_at' => $asset->transcript_reviewed_at?->toIso8601String(),
            'approved_at' => $asset->approved_at?->toIso8601String(),
            'review_notes' => $asset->review_notes,
            'questions_count' => (int) ($asset->questions_count ?? 0),
            'listening_questions_count' => (int) ($asset->listening_questions_count ?? 0),
            'is_attached_to_listening_question' => (int) ($asset->listening_questions_count ?? 0) > 0,
            'created_at' => $asset->created_at->toDateString(),
        ];
    }

    /**
     * @param  Builder<AudioAsset>  $query
     * @return Builder<AudioAsset>
     */
    private function applyFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'needs_review' => $query->where(fn (Builder $query): Builder => $query
                ->where('is_real_audio', false)
                ->orWhereNull('approved_at')
                ->orWhereNull('transcript_reviewed_at')
                ->orWhere(fn (Builder $query): Builder => $query->whereNull('transcript')->orWhere('transcript', ''))),
            'approved' => $query
                ->where('is_real_audio', true)
                ->whereNotNull('approved_at')
                ->whereNotNull('transcript_reviewed_at'),
            'real_audio' => $query->where('is_real_audio', true),
            'transcript_only' => $query->where('is_real_audio', false),
            'missing_transcript' => $query->where(fn (Builder $query): Builder => $query->whereNull('transcript')->orWhere('transcript', '')),
            'not_attached' => $query->doesntHave('questions'),
            'used_by_listening' => $query->whereHas('questions', fn (Builder $query): Builder => $query->where('section_type', SkillType::Listening)),
            default => $query,
        };
    }

    /**
     * @return list<string>
     */
    private function approvalBlockers(AudioAsset $asset): array
    {
        $blockers = [];

        if (! $asset->is_real_audio) {
            $blockers[] = 'Audio must be marked real';
        }

        if (blank($asset->transcript)) {
            $blockers[] = 'Transcript is required';
        }

        if (blank($asset->file_path) && blank($asset->audio_url)) {
            $blockers[] = 'Audio file or URL is required';
        }

        return $blockers;
    }

    /**
     * @return list<string>
     */
    private function bulkActionBlockers(AudioAsset $asset, string $action): array
    {
        if ($action === 'mark_transcript_reviewed' && blank($asset->transcript)) {
            return ['Transcript is required'];
        }

        if ($action === 'approve_selected') {
            return $this->approvalBlockers($asset);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function bulkActionUpdates(AudioAsset $asset, string $action, int $adminId, ?string $reviewNotes): array
    {
        return match ($action) {
            'mark_real_audio' => [
                'is_real_audio' => true,
                'review_notes' => $reviewNotes,
            ],
            'mark_transcript_reviewed' => [
                'transcript_reviewed_at' => $asset->transcript_reviewed_at ?? now(),
                'review_notes' => $reviewNotes,
            ],
            'approve_selected' => [
                'status' => 'ready',
                'transcript_reviewed_at' => $asset->transcript_reviewed_at ?? now(),
                'approved_at' => $asset->approved_at ?? now(),
                'approved_by' => $adminId,
                'review_notes' => $reviewNotes,
            ],
            'needs_review' => [
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
                'review_notes' => $reviewNotes,
            ],
            'archive' => [
                'status' => 'archived',
                'approved_at' => null,
                'approved_by' => null,
                'review_notes' => $reviewNotes,
            ],
            default => [],
        };
    }
}
