<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HasSeoInput;
use Illuminate\Foundation\Http\FormRequest;

class SaveGroupRequest extends FormRequest
{
    use HasSeoInput;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'boolean'],
            ...$this->seoRules(),
        ];
    }

    /** @return array{category_id: int, name: string, description: string|null, status: bool} */
    public function payload(): array
    {
        /** @var array{category_id: int, name: string, description: string|null, status: bool} $data */
        $data = $this->safe()->only(['category_id', 'name', 'description', 'status']);

        return $data;
    }
}
