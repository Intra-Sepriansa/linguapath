<?php

namespace Database\Factories;

use App\Models\StudyPath;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPath>
 */
class StudyPathFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'TOEFL ITP 60-Day Path',
            'description' => 'A focused daily plan for Listening, Structure, and Reading.',
            'duration_days' => 60,
            'level' => 'basic',
            'is_active' => true,
        ];
    }
}
