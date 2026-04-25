<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_simulation_id', 'exam_section_id', 'question_id', 'selected_option_id', 'is_correct', 'time_spent_seconds', 'answered_at'])]
class ExamAnswer extends Model
{
    public function examSimulation(): BelongsTo
    {
        return $this->belongsTo(ExamSimulation::class);
    }

    public function examSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }
}
