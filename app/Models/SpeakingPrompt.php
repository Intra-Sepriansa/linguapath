<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'prompt_type', 'skill_level', 'prompt', 'sample_answer', 'focus_points', 'preparation_seconds', 'response_seconds', 'is_active'])]
class SpeakingPrompt extends Model
{
    public function attempts(): HasMany
    {
        return $this->hasMany(SpeakingAttempt::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'focus_points' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
