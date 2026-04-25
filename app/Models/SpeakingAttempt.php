<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'speaking_prompt_id', 'recording_path', 'transcript', 'duration_seconds', 'word_count', 'filler_word_count', 'pronunciation_score', 'fluency_score', 'grammar_score', 'vocabulary_score', 'confidence_score', 'self_rating', 'feedback', 'attempted_at'])]
class SpeakingAttempt extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(SpeakingPrompt::class, 'speaking_prompt_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feedback' => 'array',
            'attempted_at' => 'datetime',
        ];
    }
}
