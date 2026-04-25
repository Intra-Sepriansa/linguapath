<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkReviewAudioAssetsRequest extends FormRequest
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
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'exists:audio_assets,id'],
            'action' => ['required', 'string', Rule::in([
                'mark_real_audio',
                'mark_transcript_reviewed',
                'approve_selected',
                'needs_review',
                'archive',
            ])],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
