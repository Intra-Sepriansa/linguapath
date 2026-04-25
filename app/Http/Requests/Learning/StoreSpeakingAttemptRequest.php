<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpeakingAttemptRequest extends FormRequest
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
            'speaking_prompt_id' => ['required', 'integer', 'exists:speaking_prompts,id'],
            'transcript' => ['nullable', 'string', 'max:5000'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:600'],
            'self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
