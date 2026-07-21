<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveCategoryRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'boolean'],
        ];
    }

    /** @return array{name: string, description: string|null, status: bool} */
    public function payload(): array
    {
        /** @var array{name: string, description: string|null, status: bool} */
        return $this->validated();
    }
}
