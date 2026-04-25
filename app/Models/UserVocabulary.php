<?php

namespace App\Models;

use App\Enums\VocabularyStatus;
use Database\Factories\UserVocabularyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'vocabulary_id', 'status', 'review_count', 'last_reviewed_at'])]
class UserVocabulary extends Model
{
    /** @use HasFactory<UserVocabularyFactory> */
    use HasFactory;

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
            'last_reviewed_at' => 'datetime',
        ];
    }
}
