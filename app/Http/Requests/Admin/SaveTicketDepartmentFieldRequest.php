<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketDepartmentField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTicketDepartmentFieldRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(TicketDepartmentField::TYPES))],
            'description' => ['nullable', 'string', 'max:255'],
            'validation' => ['nullable', 'string', 'max:255'],
            'select_options' => [
                'nullable', 'string', 'max:2000',
                Rule::requiredIf($this->input('type') === 'dropdown'),
            ],
            'required' => ['required', 'boolean'],
            'admin_only' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'select_options.required' => __('Drop down fields need at least one option (one per line).'),
        ];
    }
}
