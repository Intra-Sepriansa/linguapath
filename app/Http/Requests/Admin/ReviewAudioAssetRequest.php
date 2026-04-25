<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewAudioAssetRequest extends FormRequest
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
            'transcript_reviewed' => ['required', 'boolean'],
            'approved' => ['required', 'boolean'],
            'status' => ['required', 'string', Rule::in(['draft', 'ready', 'archived'])],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
