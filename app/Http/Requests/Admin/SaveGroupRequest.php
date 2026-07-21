<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveGroupRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'boolean'],
        ];
    }

    /** @return array{category_id: int, name: string, description: string|null, status: bool} */
    public function payload(): array
    {
        /** @var array{category_id: int, name: string, description: string|null, status: bool} */
        return $this->validated();
    }
}
