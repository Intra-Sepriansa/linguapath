<?php

namespace App\Http\Requests\Learning;

use App\Enums\PracticeMode;
use App\Enums\SkillType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPracticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_type' => ['required', 'string', Rule::in($this->values(SkillType::cases()))],
            'mode' => ['required', 'string', Rule::in($this->values(PracticeMode::cases()))],
            'study_day_id' => ['nullable', 'integer', 'exists:study_days,id'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, string>
     */
    private function values(array $cases): array
    {
        return array_map(fn (\BackedEnum $case): string => $case->value, $cases);
    }
}
