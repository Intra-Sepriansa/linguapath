<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
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
            'target_score' => 500,
            'current_level' => 'basic',
            'exam_date' => fake()->optional()->dateTimeBetween('+30 days', '+120 days'),
            'daily_goal_minutes' => 90,
            'preferred_study_time' => 'evening',
        ];
    }
}
