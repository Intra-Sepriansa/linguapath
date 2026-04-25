<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'kind', 'title', 'description', 'action_label', 'action_url', 'priority', 'status', 'metadata', 'due_at', 'resolved_at'])]
class Recommendation extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
