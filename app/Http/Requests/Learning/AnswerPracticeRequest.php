<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class AnswerPracticeRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['required', 'integer', 'exists:question_options,id'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }
}
