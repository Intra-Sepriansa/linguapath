<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'skill_tag_id', 'score', 'confidence', 'attempts', 'correct_attempts', 'last_practiced_at'])]
class UserSkillMastery extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillTag(): BelongsTo
    {
        return $this->belongsTo(SkillTag::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_practiced_at' => 'datetime',
        ];
    }
}
