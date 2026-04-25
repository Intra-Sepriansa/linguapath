<?php

namespace Database\Factories;

use App\Enums\MistakeType;
use App\Enums\ReviewStatus;
use App\Enums\SkillType;
use App\Models\MistakeJournal;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MistakeJournal>
 */
class MistakeJournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'section_type' => SkillType::Structure,
            'mistake_type' => MistakeType::Grammar,
            'user_answer' => 'study',
            'correct_answer' => 'studies',
            'note' => 'Singular subject needs -s.',
            'review_status' => ReviewStatus::New,
            'reviewed_at' => null,
        ];
    }
}
