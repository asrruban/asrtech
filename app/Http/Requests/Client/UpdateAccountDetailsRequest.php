<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountDetailsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'address_1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'newsletter' => ['required', 'boolean'],
        ];
    }
}
