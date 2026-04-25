<?php

namespace Database\Factories;

use App\Enums\PracticeMode;
use App\Enums\SkillType;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeSession>
 */
class PracticeSessionFactory extends Factory
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
            'section_type' => SkillType::Structure,
            'mode' => PracticeMode::Quick,
            'total_questions' => 10,
            'correct_answers' => 0,
            'score' => 0,
            'duration_seconds' => 0,
            'started_at' => now(),
        ];
    }
}
