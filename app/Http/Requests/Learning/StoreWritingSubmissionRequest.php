<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class StoreWritingSubmissionRequest extends FormRequest
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
            'writing_prompt_id' => ['required', 'integer', 'exists:writing_prompts,id'],
            'response_text' => ['required', 'string', 'min:20', 'max:10000'],
        ];
    }
}
