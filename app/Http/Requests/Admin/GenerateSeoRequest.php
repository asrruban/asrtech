<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateSeoRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['category', 'subcategory'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:2000'],
        ];
    }

    /** @return array{type: string, name: string, description: string|null, parent_name: string|null, canonical_url: string|null} */
    public function context(): array
    {
        /** @var array{type: string, name: string, description?: string|null, parent_name?: string|null, canonical_url?: string|null} $data */
        $data = $this->validated();

        return [
            'type' => $data['type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
        ];
    }
}
