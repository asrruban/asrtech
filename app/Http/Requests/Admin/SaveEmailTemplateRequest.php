<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEmailTemplateRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('email_templates', 'name')
                    ->ignore($this->route('emailtemplate')),
            ],
            'category' => ['required', Rule::in(array_keys(EmailTemplate::CATEGORIES))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
