<?php

namespace App\Models;

use App\Enums\VocabularyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'vocabulary_id', 'status', 'user_difficulty_rating', 'review_count', 'correct_count', 'wrong_count', 'ease_score', 'interval_days', 'due_at', 'last_reviewed_at'])]
class UserVocabularyReview extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VocabularyStatus::class,
            'ease_score' => 'float',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }
}
