<?php

namespace App\Models;

use Database\Factories\StudyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'study_day_id', 'minutes_spent', 'completed_lessons', 'completed_questions', 'accuracy', 'log_date'])]
class StudyLog extends Model
{
    /** @use HasFactory<StudyLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyDay(): BelongsTo
    {
        return $this->belongsTo(StudyDay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accuracy' => 'float',
            'log_date' => 'date',
        ];
    }
}
