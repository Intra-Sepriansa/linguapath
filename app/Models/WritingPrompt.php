<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'prompt_type', 'skill_level', 'prompt', 'suggested_minutes', 'min_words', 'rubric', 'sample_response', 'is_active'])]
class WritingPrompt extends Model
{
    public function submissions(): HasMany
    {
        return $this->hasMany(WritingSubmission::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rubric' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
