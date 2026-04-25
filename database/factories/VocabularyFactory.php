<?php

namespace Database\Factories;

use App\Enums\SkillType;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vocabulary>
 */
class VocabularyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word' => fake()->unique()->word(),
            'meaning' => fake()->word(),
            'usage_note' => 'Digunakan untuk mengenali makna kata dalam konteks akademik TOEFL.',
            'example_sentence' => fake()->sentence(),
            'example_translation' => fake()->sentence(),
            'category' => 'academic',
            'difficulty' => 'beginner',
            'skill_type' => SkillType::Vocabulary,
        ];
    }
}
