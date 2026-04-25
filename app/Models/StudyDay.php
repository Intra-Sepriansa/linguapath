<?php

namespace App\Models;

use App\Enums\SkillType;
use Database\Factories\StudyDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['study_path_id', 'day_number', 'title', 'focus_skill', 'objective', 'estimated_minutes'])]
class StudyDay extends Model
{
    /** @use HasFactory<StudyDayFactory> */
    use HasFactory;

    public function studyPath(): BelongsTo
    {
        return $this->belongsTo(StudyPath::class);
    }

    public function lesson(): HasOne
    {
        return $this->hasOne(Lesson::class);
    }

    public function studyLogs(): HasMany
    {
        return $this->hasMany(StudyLog::class);
    }

    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'focus_skill' => SkillType::class,
        ];
    }
}
