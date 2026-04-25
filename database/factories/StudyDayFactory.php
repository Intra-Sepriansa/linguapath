<?php

namespace Database\Factories;

use App\Enums\SkillType;
use App\Models\StudyPath;
use App\Models\StudyDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyDay>
 */
class StudyDayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_path_id' => StudyPath::factory(),
            'day_number' => fake()->unique()->numberBetween(1, 60),
            'title' => fake()->sentence(3),
            'focus_skill' => SkillType::Structure,
            'objective' => fake()->sentence(),
            'estimated_minutes' => 90,
        ];
    }
}
