<?php

namespace App\Models;

use App\Enums\SkillType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_simulation_id', 'section_type', 'position', 'status', 'duration_seconds', 'total_questions', 'correct_answers', 'estimated_scaled_score', 'started_at', 'ends_at', 'finished_at', 'submitted_at', 'submission_reason'])]
class ExamSection extends Model
{
    public function examSimulation(): BelongsTo
    {
        return $this->belongsTo(ExamSimulation::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_type' => SkillType::class,
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'finished_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
