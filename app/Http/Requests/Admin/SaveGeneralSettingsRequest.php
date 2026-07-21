<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveGeneralSettingsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'site_url' => ['required', 'url', 'max:2000'],
            'app_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'url', 'max:2000'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone:all'],
            'facebook_url' => ['nullable', 'url', 'max:2000'],
            'linkedin_url' => ['nullable', 'url', 'max:2000'],
            'github_url' => ['nullable', 'url', 'max:2000'],
            'allow_registration' => ['required', 'boolean'],
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'records_per_page' => ['required', 'integer', 'in:10,15,25,50'],
            'auto_provision_licenses' => ['required', 'boolean'],
            'send_invoice_reminders' => ['required', 'boolean'],
            'invoice_reminder_days' => ['required', 'integer', 'min:1', 'max:60'],
            'send_subscription_reminders' => ['required', 'boolean'],
            'subscription_reminder_days' => ['required', 'integer', 'min:1', 'max:60'],
            'subscription_grace_days' => ['required', 'integer', 'min:0', 'max:60'],
            'mail_signature' => ['nullable', 'string', 'max:1000'],
            'company_name' => ['required', 'string', 'max:150'],
            'activity_log_limit' => ['required', 'integer', 'min:10', 'max:100000'],
            'mail_disable' => ['required', 'boolean'],
            'mail_bcc' => ['nullable', 'string', 'max:500'],
            'email_header_content' => ['nullable', 'string', 'max:10000'],
            'email_footer_content' => ['nullable', 'string', 'max:10000'],
            'login_max_attempts' => ['required', 'integer', 'min:3', 'max:100'],
            'login_decay_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'email_otp_ttl_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'date_format' => ['required', 'in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD,DD.MM.YYYY'],
            'default_country' => ['required', 'string', 'size:2', 'uppercase'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'require_tos_accept' => ['required', 'boolean'],
            'terms_url' => ['nullable', 'required_if:require_tos_accept,true', 'url', 'max:2000'],
            'invoice_number_prefix' => ['required', 'string', 'alpha_num:ascii', 'uppercase', 'max:8'],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_pay_to' => ['nullable', 'string', 'max:1000'],
            'invoice_footer_note' => ['nullable', 'string', 'max:1000'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:1000'],
            'github_client_id' => ['nullable', 'string', 'max:255'],
            'github_client_secret' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
