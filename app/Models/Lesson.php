<?php

namespace App\Models;

use App\Enums\SkillType;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['study_day_id', 'title', 'summary', 'content', 'skill_type', 'difficulty'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    public function studyDay(): BelongsTo
    {
        return $this->belongsTo(StudyDay::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LessonSection::class)->orderBy('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'skill_type' => SkillType::class,
        ];
    }
}
