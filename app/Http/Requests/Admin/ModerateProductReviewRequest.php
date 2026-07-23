<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateProductReviewRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProductReviewStatus::class)],
            'moderation_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
