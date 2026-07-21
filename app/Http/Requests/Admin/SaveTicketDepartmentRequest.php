<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTicketDepartmentRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'assigned_admin_ids' => ['array'],
            'assigned_admin_ids.*' => ['integer', Rule::exists('admins', 'id')],
            'clients_only' => ['required', 'boolean'],
            'pipe_replies_only' => ['required', 'boolean'],
            'no_autoresponder' => ['required', 'boolean'],
            'feedback_request' => ['required', 'boolean'],
            'prevent_client_closure' => ['required', 'boolean'],
            'hidden' => ['required', 'boolean'],
            'mail_provider' => ['required', Rule::in(array_keys(TicketDepartment::MAIL_PROVIDERS))],
            'mail_hostname' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'between:0,65535'],
            'mail_email' => ['nullable', 'email', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_client_id' => ['nullable', 'string', 'max:255'],
            'mail_client_secret' => ['nullable', 'string', 'max:255'],
        ];
    }
}
