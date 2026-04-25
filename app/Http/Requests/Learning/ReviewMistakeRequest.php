<?php

namespace App\Http\Requests\Learning;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewMistakeRequest extends FormRequest
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
            'review_status' => ['required', 'string', Rule::in(array_map(fn (ReviewStatus $status): string => $status->value, ReviewStatus::cases()))],
        ];
    }
}
