<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'topic', 'body', 'word_count', 'difficulty', 'source', 'status', 'reviewed_at'])]
class Passage extends Model
{
    public const array DIFFICULTIES = [
        'beginner',
        'elementary',
        'intermediate',
        'upper_intermediate',
        'advanced',
    ];

    public const array STATUSES = [
        'draft',
        'ready',
        'published',
        'reviewed',
        'archived',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public static function countWords(string $text): int
    {
        $plainText = trim(strip_tags($text));

        if ($plainText === '') {
            return 0;
        }

        preg_match_all("/[\p{L}\p{N}]+(?:[-'][\p{L}\p{N}]+)*/u", $plainText, $matches);

        return count($matches[0]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }
}
