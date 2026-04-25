<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'audio_url', 'file_path', 'mime_type', 'file_size', 'uploaded_by', 'is_real_audio', 'playback_limit_exam', 'status', 'transcript', 'speaker_notes', 'duration_seconds', 'accent', 'speed', 'source'])]
class AudioAsset extends Model
{
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function playbackUrl(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return $this->audio_url;
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
        ];
    }
}
