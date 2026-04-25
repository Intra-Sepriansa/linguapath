<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreAudioAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'audio_file' => [
                'required',
                File::types(['mp3', 'wav', 'm4a'])->max(20 * 1024),
            ],
            'transcript' => ['required', 'string', 'max:20000'],
            'speaker_notes' => ['nullable', 'string', 'max:5000'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'accent' => ['nullable', 'string', 'max:50'],
            'speed' => ['nullable', 'numeric', 'min:0.5', 'max:1.5'],
            'playback_limit_exam' => ['nullable', 'integer', 'min:1', 'max:3'],
            'status' => ['nullable', 'string', 'in:draft,ready,archived'],
        ];
    }
}
