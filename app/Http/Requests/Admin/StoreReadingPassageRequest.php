<?php

namespace App\Http\Requests\Admin;

use App\Models\Passage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReadingPassageRequest extends FormRequest
{
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
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'passage_text' => ['required', 'string', 'max:12000'],
            'difficulty' => ['required', 'string', Rule::in(Passage::DIFFICULTIES)],
            'status' => ['required', 'string', Rule::in(Passage::STATUSES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $wordCount = Passage::countWords((string) $this->input('passage_text', ''));

            if (! in_array($this->input('status'), ['ready', 'published', 'reviewed'], true)) {
                return;
            }

            if ($wordCount < 300) {
                $validator->errors()->add('passage_text', 'Exam-ready TOEFL-style reading passages must contain at least 300 words.');
            }

            if ($wordCount > 700) {
                $validator->errors()->add('passage_text', 'Exam-ready TOEFL-style reading passages must contain no more than 700 words.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'passage_text.required' => 'The passage text field is required.',
        ];
    }
}
