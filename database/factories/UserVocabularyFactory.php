<?php

namespace Database\Factories;

use App\Enums\VocabularyStatus;
use App\Models\User;
use App\Models\UserVocabulary;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserVocabulary>
 */
class UserVocabularyFactory extends Factory
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
            'vocabulary_id' => Vocabulary::factory(),
            'status' => VocabularyStatus::Learning,
            'review_count' => 0,
            'last_reviewed_at' => null,
        ];
    }
}
