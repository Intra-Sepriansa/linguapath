<?php

namespace App\Models;

use App\Enums\PracticeMode;
use App\Enums\SkillType;
use Database\Factories\PracticeSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'study_day_id', 'section_type', 'mode', 'total_questions', 'correct_answers', 'score', 'duration_seconds', 'started_at', 'finished_at'])]
class PracticeSession extends Model
{
    /** @use HasFactory<PracticeSessionFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyDay(): BelongsTo
    {
        return $this->belongsTo(StudyDay::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PracticeAnswer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_type' => SkillType::class,
            'mode' => PracticeMode::class,
            'score' => 'float',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
