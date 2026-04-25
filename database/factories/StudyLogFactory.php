<?php

namespace Database\Factories;

use App\Models\StudyDay;
use App\Models\StudyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyLog>
 */
class StudyLogFactory extends Factory
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
            'study_day_id' => StudyDay::factory(),
            'minutes_spent' => 30,
            'completed_lessons' => 1,
            'completed_questions' => 10,
            'accuracy' => 70,
            'log_date' => today(),
        ];
    }
}
