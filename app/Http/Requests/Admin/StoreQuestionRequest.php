<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuestionType;
use App\Enums\SkillType;
use App\Models\Passage;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    /**
     * @return list<string>
     */
    public static function optionLabels(): array
    {
        return ['A', 'B', 'C', 'D'];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'section_type' => ['required', 'string', Rule::in([
                SkillType::Listening->value,
                SkillType::Structure->value,
                SkillType::Reading->value,
            ])],
            'status' => ['required', 'string', Rule::in(Question::STATUSES)],
            'question_type' => [Rule::requiredIf($this->isExamReady()), 'nullable', 'string', Rule::in($this->allowedQuestionTypes())],
            'difficulty' => [Rule::requiredIf($this->isExamReady()), 'nullable', 'string', Rule::in(Passage::DIFFICULTIES)],
            'question_text' => ['required', 'string', 'min:10', 'max:20000'],
            'explanation' => [Rule::requiredIf($this->isExamReady()), 'nullable', 'string', 'max:20000'],
            'evidence_sentence' => [Rule::requiredIf($this->isExamReady() && $this->input('section_type') === SkillType::Reading->value), 'nullable', 'string', 'max:5000'],
            'passage_id' => [Rule::requiredIf($this->isExamReady() && $this->input('section_type') === SkillType::Reading->value), 'nullable', 'integer', 'exists:passages,id'],
            'audio_asset_id' => [Rule::requiredIf($this->isExamReady() && $this->input('section_type') === SkillType::Listening->value), 'nullable', 'integer', 'exists:audio_assets,id'],
            'skill_tag_id' => [Rule::requiredIf($this->isExamReady()), 'nullable', 'integer', 'exists:skill_tags,id'],
            'options' => ['required', 'array', 'size:4'],
            'options.*.label' => ['required', 'string', Rule::in(self::optionLabels())],
            'options.*.text' => ['required', 'string', 'min:1', 'max:10000'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $options = collect($this->input('options', []));
            $labels = $options->pluck('label')->filter()->values();
            $correctCount = $options
                ->filter(fn (mixed $option): bool => is_array($option) && filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
                ->count();

            if ($labels->unique()->count() !== $labels->count() || $labels->sort()->values()->all() !== self::optionLabels()) {
                $validator->errors()->add('options', 'Options must use the unique labels A, B, C, and D.');
            }

            if ($correctCount !== 1) {
                $validator->errors()->add('options', 'Exactly one answer option must be marked correct.');
            }

            if ($this->input('section_type') !== SkillType::Reading->value && filled($this->input('passage_id'))) {
                $validator->errors()->add('passage_id', 'Reading passage can only be attached to reading questions.');
            }

            if ($this->input('section_type') !== SkillType::Listening->value && filled($this->input('audio_asset_id'))) {
                $validator->errors()->add('audio_asset_id', 'Audio asset can only be attached to listening questions.');
            }
        });
    }

    private function isExamReady(): bool
    {
        return in_array($this->input('status'), Question::ACTIVE_STATUSES, true);
    }

    /**
     * @return list<string>
     */
    private function allowedQuestionTypes(): array
    {
        return collect(QuestionType::cases())
            ->reject(fn (QuestionType $type): bool => in_array($type, [
                QuestionType::ReadAloud,
                QuestionType::Shadowing,
                QuestionType::Roleplay,
                QuestionType::WritingPrompt,
            ], true))
            ->map(fn (QuestionType $type): string => $type->value)
            ->values()
            ->all();
    }
}
