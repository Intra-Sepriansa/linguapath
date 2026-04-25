<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'audio_url', 'file_path', 'mime_type', 'file_size', 'uploaded_by', 'is_real_audio', 'playback_limit_exam', 'status', 'transcript', 'transcript_reviewed_at', 'approved_at', 'approved_by', 'review_notes', 'speaker_notes', 'duration_seconds', 'accent', 'speed', 'source'])]
class AudioAsset extends Model
{
    public const array EXAM_READY_STATUSES = [
        'ready',
        'published',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function playbackUrl(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return $this->audio_url;
    }

    public function isApprovedForExam(): bool
    {
        return $this->is_real_audio
            && in_array($this->status, self::EXAM_READY_STATUSES, true)
            && $this->approved_at !== null
            && $this->transcript_reviewed_at !== null
            && filled($this->transcript)
            && (filled($this->file_path) || filled($this->audio_url));
    }

    /**
     * @return list<string>
     */
    public function qualityBadges(): array
    {
        $badges = [];

        $badges[] = $this->is_real_audio ? 'Real Audio' : 'Transcript Only';

        if (blank($this->transcript)) {
            $badges[] = 'Missing Transcript';
        }

        if ($this->transcript_reviewed_at !== null) {
            $badges[] = 'Transcript Reviewed';
        }

        if ($this->approved_at !== null) {
            $badges[] = 'Approved';
        }

        if (! $this->isApprovedForExam()) {
            $badges[] = 'Needs Review';
        }

        return $badges;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_real_audio' => 'boolean',
            'file_size' => 'integer',
            'playback_limit_exam' => 'integer',
            'speed' => 'float',
            'transcript_reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
