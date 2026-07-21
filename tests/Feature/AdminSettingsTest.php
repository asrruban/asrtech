<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Providers\SettingsServiceProvider;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The standalone Site & Branding page was merged into General
 * Configuration — these tests cover the redirect and the moved fields.
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_settings_url_redirects_to_general_configuration(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings')
            ->assertRedirect('/admin/settings/general');
    }

    public function test_site_and_branding_fields_save_through_general_configuration(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'app_name' => 'ASR Tech',
            'tagline' => 'Modules and development',
            'phone' => '+8801000000000',
            'address' => 'Dhaka, Bangladesh',
            'facebook_url' => 'https://facebook.com/asrtech',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
        ])->assertRedirect('/admin/settings/general');

        $settings = app(SettingService::class);
        $this->assertSame('ASR Tech', $settings->get('app_name'));
        $this->assertSame('Modules and development', $settings->get('tagline'));
        $this->assertSame('https://facebook.com/asrtech', $settings->get('facebook_url'));
        $this->assertSame('Asia/Dhaka', $settings->get('timezone'));
        $this->assertSame('BDT', $settings->get('currency'));

        (new SettingsServiceProvider($this->app))->boot($settings);
        $this->assertSame('ASR Tech', config('app.name'));
        $this->assertSame('Modules and development', config('asrtech.tagline'));
        $this->assertSame('https://facebook.com/asrtech', config('asrtech.social.facebook'));
        $this->assertSame('Asia/Dhaka', config('app.timezone'));

        $this->get('/admin/settings/general')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/General')
                ->where('settings.app_name', 'ASR Tech')
                ->where('settings.tagline', 'Modules and development')
                ->where('settings.facebook_url', 'https://facebook.com/asrtech'));
    }

    public function test_settings_require_an_admin_session(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
        $this->get('/admin/settings/general')->assertRedirect('/admin/login');
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'site_url' => 'https://asrtech.example',
            'app_name' => 'ASRTech',
            'support_email' => 'support@example.com',
            'company_name' => 'ASR Tech Ltd',
            'tagline' => null,
            'phone' => null,
            'address' => null,
            'logo_url' => null,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'facebook_url' => null,
            'linkedin_url' => null,
            'github_url' => null,
            'activity_log_limit' => 1000,
            'mail_disable' => false,
            'mail_bcc' => null,
            'email_header_content' => null,
            'email_footer_content' => null,
            'allow_registration' => true,
            'maintenance_mode' => false,
            'maintenance_message' => null,
            'records_per_page' => 15,
            'auto_provision_licenses' => true,
            'send_invoice_reminders' => false,
            'invoice_reminder_days' => 7,
            'send_subscription_reminders' => false,
            'subscription_reminder_days' => 7,
            'subscription_grace_days' => 3,
            'mail_signature' => null,
            'login_max_attempts' => 6,
            'login_decay_minutes' => 1,
            'email_otp_ttl_minutes' => 10,
            'date_format' => 'DD/MM/YYYY',
            'default_country' => 'BD',
            'currency_symbol' => '$',
            'require_tos_accept' => false,
            'terms_url' => null,
            'invoice_number_prefix' => 'INV',
            'invoice_due_days' => 14,
            'invoice_pay_to' => null,
            'invoice_footer_note' => null,
            'mail_from_name' => null,
            'mail_from_address' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
        ];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Settings Admin',
            'email' => 'settings@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
