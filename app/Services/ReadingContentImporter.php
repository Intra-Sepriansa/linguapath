<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SkillTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ReadingContentImporter
{
    /**
     * @return array{files: int, passages: int, questions: int}
     */
    public function importDirectory(string $directory): array
    {
        if (! File::isDirectory($directory)) {
            return ['files' => 0, 'passages' => 0, 'questions' => 0];
        }

        return collect(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'json')
            ->reduce(function (array $summary, $file): array {
                $result = $this->importFile($file->getPathname());

                return [
                    'files' => $summary['files'] + 1,
                    'passages' => $summary['passages'] + $result['passages'],
                    'questions' => $summary['questions'] + $result['questions'],
                ];
            }, ['files' => 0, 'passages' => 0, 'questions' => 0]);
    }

    /**
     * @return array{passages: int, questions: int}
     */
    public function importFile(string $path): array
    {
        $payload = json_decode(File::get($path), true);

        if (! is_array($payload)) {
            throw new RuntimeException("Reading content file is invalid JSON: {$path}");
        }

        return DB::transaction(function () use ($payload, $path): array {
            $passageCount = 0;
            $questionCount = 0;

            foreach ($payload['passages'] ?? [] as $passageData) {
                $wordCount = str_word_count($passageData['body']);

                if ($wordCount < 300 || $wordCount > 700) {
                    throw new RuntimeException("Reading passage [{$passageData['title']}] must be 300-700 words; got {$wordCount}.");
                }

                $passage = Passage::query()->updateOrCreate(
                    ['title' => $passageData['title']],
                    [
                        'topic' => $passageData['topic'],
                        'body' => $passageData['body'],
                        'word_count' => $wordCount,
                        'difficulty' => $passageData['difficulty'],
                        'source' => basename($path),
                        'status' => 'ready',
                        'reviewed_at' => now(),
                    ]
                );
                $passageCount++;

                foreach ($passageData['questions'] as $questionData) {
                    $skillTag = SkillTag::query()->firstOrCreate(
                        ['code' => $questionData['skill_tag']],
                        [
                            'name' => Str::headline($questionData['skill_tag']),
                            'domain' => 'reading',
                            'description' => 'Imported TOEFL-style reading content.',
                            'difficulty' => $questionData['difficulty'],
                        ]
                    );

                    $question = Question::query()->updateOrCreate(
                        [
                            'passage_id' => $passage->id,
                            'question_text' => $questionData['question_text'],
                        ],
                        [
                            'lesson_id' => null,
                            'skill_tag_id' => $skillTag->id,
                            'section_type' => SkillType::Reading,
                            'question_type' => QuestionType::from($questionData['question_type']),
                            'difficulty' => $questionData['difficulty'],
                            'exam_eligible' => true,
                            'passage_text' => $passage->body,
                            'explanation' => $questionData['explanation'],
                            'evidence_sentence' => $questionData['evidence_sentence'],
                            'why_correct' => $questionData['explanation'],
                            'why_wrong' => 'The distractors either overgeneralize the passage or point to details that do not answer this question.',
                        ]
                    );

                    foreach ($questionData['options'] as $label => $optionText) {
                        QuestionOption::query()->updateOrCreate(
                            ['question_id' => $question->id, 'option_label' => $label],
                            [
                                'option_text' => $optionText,
                                'is_correct' => $label === $questionData['correct'],
                            ]
                        );
                    }

                    $questionCount++;
                }
            }

            DB::table('content_imports')->updateOrInsert(
                [
                    'kind' => 'reading',
                    'source_path' => str_replace(base_path().'/', '', $path),
                    'checksum' => hash_file('sha256', $path),
                ],
                [
                    'status' => 'completed',
                    'imported_records' => $passageCount + $questionCount,
                    'notes' => "Imported {$passageCount} passages and {$questionCount} questions.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return ['passages' => $passageCount, 'questions' => $questionCount];
        });
    }
}
