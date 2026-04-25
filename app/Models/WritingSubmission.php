<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'writing_prompt_id', 'response_text', 'word_count', 'task_score', 'grammar_score', 'vocabulary_score', 'coherence_score', 'overall_score', 'feedback', 'submitted_at'])]
class WritingSubmission extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(WritingPrompt::class, 'writing_prompt_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feedback' => 'array',
            'submitted_at' => 'datetime',
        ];
    }
}
