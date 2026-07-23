<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProductContentRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'compatibility' => ['nullable', 'string', 'max:255'],
            'php_compatibility' => ['nullable', 'string', 'max:255'],
            'existing_details' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string|null> */
    public function context(): array
    {
        /** @var array<string, string|null> $data */
        $data = $this->validated();

        return $data;
    }
}
