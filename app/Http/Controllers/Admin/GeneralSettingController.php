<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGeneralSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WHMCS-style general configuration backed by the settings
 * key-value store (the ASRTech equivalent of tblconfiguration).
 */
class GeneralSettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Configuration/Settings/General', [
            'settings' => [
                'site_url' => $this->settings->get('site_url', (string) config('app.url')),
                'company_name' => $this->settings->get('company_name', (string) config('asrtech.company_name')),
                'app_name' => $this->settings->get('app_name', (string) config('app.name')),
                'support_email' => $this->settings->get('support_email'),
                'tagline' => $this->settings->get('tagline', (string) config('asrtech.tagline')),
                'phone' => $this->settings->get('phone'),
                'address' => $this->settings->get('address'),
                'logo_url' => $this->settings->get('logo_url'),
                'currency' => $this->settings->get('currency', 'USD'),
                'timezone' => $this->settings->get('timezone', (string) config('app.timezone')),
                'facebook_url' => $this->settings->get('facebook_url'),
                'linkedin_url' => $this->settings->get('linkedin_url'),
                'github_url' => $this->settings->get('github_url'),
                'activity_log_limit' => (int) $this->settings->get('activity_log_limit', '1000'),
                'mail_disable' => $this->settings->get('mail_disable', '0') === '1',
                'mail_bcc' => $this->settings->get('mail_bcc'),
                'email_header_content' => $this->settings->get('email_header_content'),
                'email_footer_content' => $this->settings->get('email_footer_content'),
                'branding_logo_url' => $this->settings->get('branding_logo_url'),
                'branding_logo_light_url' => $this->settings->get('branding_logo_light_url'),
                'branding_logo_dark_url' => $this->settings->get('branding_logo_dark_url'),
                'branding_favicon_url' => $this->settings->get('branding_favicon_url'),
                'allow_registration' => $this->settings->get('allow_registration', '1') === '1',
                'maintenance_mode' => $this->settings->get('maintenance_mode', '0') === '1',
                'maintenance_message' => $this->settings->get('maintenance_message'),
                'records_per_page' => (int) $this->settings->get('records_per_page', '15'),
                'auto_provision_licenses' => $this->settings->get('auto_provision_licenses', '1') === '1',
                'send_invoice_reminders' => $this->settings->get('send_invoice_reminders', '0') === '1',
                'invoice_reminder_days' => (int) $this->settings->get('invoice_reminder_days', '7'),
                'send_subscription_reminders' => $this->settings->get('send_subscription_reminders', '0') === '1',
                'subscription_reminder_days' => (int) $this->settings->get('subscription_reminder_days', '7'),
                'subscription_grace_days' => (int) $this->settings->get('subscription_grace_days', '3'),
                'mail_signature' => $this->settings->get('mail_signature'),
                'login_max_attempts' => (int) $this->settings->get('login_max_attempts', '6'),
                'login_decay_minutes' => (int) $this->settings->get('login_decay_minutes', '1'),
                'email_otp_ttl_minutes' => (int) $this->settings->get('email_otp_ttl_minutes', '10'),
                'date_format' => $this->settings->get('date_format', 'DD/MM/YYYY'),
                'default_country' => $this->settings->get('default_country', 'US'),
                'currency_symbol' => $this->settings->get('currency_symbol', '$'),
                'require_tos_accept' => $this->settings->get('require_tos_accept', '0') === '1',
                'terms_url' => $this->settings->get('terms_url'),
                'invoice_number_prefix' => $this->settings->get('invoice_number_prefix', 'INV'),
                'invoice_due_days' => (int) $this->settings->get('invoice_due_days', '14'),
                'invoice_pay_to' => $this->settings->get('invoice_pay_to'),
                'invoice_footer_note' => $this->settings->get('invoice_footer_note'),
                'mail_from_name' => $this->settings->get('mail_from_name'),
                'mail_from_address' => $this->settings->get('mail_from_address'),
                'smtp_host' => $this->settings->get('smtp_host'),
                'smtp_port' => $this->settings->get('smtp_port'),
                'smtp_username' => $this->settings->get('smtp_username'),
                'smtp_password_configured' => filled($this->settings->get('smtp_password')),
                'google_client_id' => $this->settings->get('google_client_id'),
                'google_client_secret_configured' => filled($this->settings->get('google_client_secret')),
                'github_client_id' => $this->settings->get('github_client_id'),
                'github_client_secret_configured' => filled($this->settings->get('github_client_secret')),
            ],
        ]);
    }

    public function update(SaveGeneralSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ([
            'site_url',
            'company_name',
            'app_name',
            'support_email',
            'tagline',
            'phone',
            'address',
            'logo_url',
            'currency',
            'timezone',
            'facebook_url',
            'linkedin_url',
            'github_url',
            'date_format',
            'default_country',
            'currency_symbol',
            'terms_url',
            'maintenance_message',
            'mail_signature',
            'mail_bcc',
            'email_header_content',
            'email_footer_content',
            'invoice_number_prefix',
            'invoice_pay_to',
            'invoice_footer_note',
            'mail_from_name',
            'mail_from_address',
            'smtp_host',
            'smtp_username',
            'google_client_id',
            'github_client_id',
        ] as $key) {
            $this->settings->put($key, isset($data[$key]) ? (string) $data[$key] : null);
        }

        foreach ([
            'allow_registration',
            'require_tos_accept',
            'maintenance_mode',
            'auto_provision_licenses',
            'send_invoice_reminders',
            'send_subscription_reminders',
            'mail_disable',
        ] as $key) {
            $this->settings->put($key, $request->boolean($key) ? '1' : '0');
        }

        foreach ([
            'invoice_due_days',
            'records_per_page',
            'invoice_reminder_days',
            'subscription_reminder_days',
            'subscription_grace_days',
            'login_max_attempts',
            'login_decay_minutes',
            'email_otp_ttl_minutes',
            'activity_log_limit',
        ] as $key) {
            $this->settings->put($key, (string) $request->integer($key));
        }

        $this->settings->put('smtp_port', $data['smtp_port'] === null ? null : (string) $data['smtp_port']);

        // Write-only secrets: keep the stored value when left blank.
        foreach (['smtp_password', 'google_client_secret', 'github_client_secret'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                $this->settings->put($secret, (string) $data[$secret]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General configuration saved.')]);

        return redirect()->route('admin.settings.general.edit');
    }
}
