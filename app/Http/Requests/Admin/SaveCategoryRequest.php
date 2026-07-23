<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HasSeoInput;
use Illuminate\Foundation\Http\FormRequest;

class SaveCategoryRequest extends FormRequest
{
    use HasSeoInput;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'boolean'],
            ...$this->seoRules(),
        ];
    }

    /** @return array{name: string, description: string|null, status: bool} */
    public function payload(): array
    {
        /** @var array{name: string, description: string|null, status: bool} $data */
        $data = $this->safe()->only(['name', 'description', 'status']);

        return $data;
    }
}
