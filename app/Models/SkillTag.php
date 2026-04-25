<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'domain', 'description', 'difficulty'])]
class SkillTag extends Model
{
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function userSkillMasteries(): HasMany
    {
        return $this->hasMany(UserSkillMastery::class);
    }
}
