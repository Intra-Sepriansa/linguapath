<?php

namespace App\Models;

use Database\Factories\StudyPathFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'duration_days', 'level', 'is_active'])]
class StudyPath extends Model
{
    /** @use HasFactory<StudyPathFactory> */
    use HasFactory;

    public function studyDays(): HasMany
    {
        return $this->hasMany(StudyDay::class)->orderBy('day_number');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
