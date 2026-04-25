<?php

namespace App\Http\Requests\Learning;

use App\Enums\VocabularyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkVocabularyRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(array_map(fn (VocabularyStatus $status): string => $status->value, VocabularyStatus::cases()))],
        ];
    }
}
