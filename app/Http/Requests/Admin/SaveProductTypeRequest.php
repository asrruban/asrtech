<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProductTypeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slug = Str::slug((string) ($this->input('slug') ?: $name));

        $this->merge([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var ProductType|null $productType */
        $productType = $this->route('product_type');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('product_types', 'slug')->ignore($productType),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'boolean'],
        ];
    }

    /** @return array{name: string, slug: string, description: string|null, status: bool} */
    public function payload(): array
    {
        /** @var array{name: string, slug: string, description: string|null, status: bool} */
        return $this->validated();
    }
}
