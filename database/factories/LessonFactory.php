<?php

namespace Database\Factories;

use App\Enums\SkillType;
use App\Models\Lesson;
use App\Models\StudyDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_day_id' => StudyDay::factory(),
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(),
            'content' => [
                'goal' => fake()->sentence(),
                'pattern' => 'Subject + Verb',
                'tasks' => ['Read lesson', 'Practice questions', 'Review mistakes'],
            ],
            'skill_type' => SkillType::Structure,
            'difficulty' => 'beginner',
        ];
    }
}
