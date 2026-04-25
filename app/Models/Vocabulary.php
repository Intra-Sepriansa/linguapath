<?php

namespace App\Models;

use App\Enums\SkillType;
use Database\Factories\VocabularyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['word', 'pronunciation', 'meaning', 'usage_note', 'example_sentence', 'example_translation', 'category', 'difficulty', 'frequency_rank', 'synonyms', 'antonyms', 'word_family', 'collocations', 'skill_type'])]
class Vocabulary extends Model
{
    /** @use HasFactory<VocabularyFactory> */
    use HasFactory;

    public function userVocabularies(): HasMany
    {
        return $this->hasMany(UserVocabulary::class);
    }

    public function userVocabularyReviews(): HasMany
    {
        return $this->hasMany(UserVocabularyReview::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skill_type' => SkillType::class,
            'synonyms' => 'array',
            'antonyms' => 'array',
            'word_family' => 'array',
            'collocations' => 'array',
        ];
    }
}
