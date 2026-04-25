<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'section_type' => SkillType::Structure,
            'question_type' => QuestionType::IncompleteSentence,
            'difficulty' => 'beginner',
            'status' => 'ready',
            'exam_eligible' => true,
            'question_text' => 'The student ___ English every morning.',
            'explanation' => 'A singular subject uses a verb with -s in the simple present.',
        ];
    }
}
