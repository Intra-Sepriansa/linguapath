<?php

namespace Database\Factories;

use App\Models\PracticeAnswer;
use App\Models\PracticeSession;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeAnswer>
 */
class PracticeAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_session_id' => PracticeSession::factory(),
            'question_id' => Question::factory(),
            'selected_option_id' => null,
            'is_correct' => false,
            'time_spent_seconds' => 0,
        ];
    }
}
