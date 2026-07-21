<?php

namespace App\Providers;

use App\Services\SettingService;
use App\Services\StorageService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    public function boot(SettingService $settings): void
    {
        try {
            if (! Schema::hasTable('configuration')) {
                return;
            }

            $values = $settings->all();

            config([
                'app.name' => Arr::get($values, 'app_name', config('app.name')),
                'app.timezone' => Arr::get($values, 'timezone', config('app.timezone')),
                'app.currency' => Arr::get($values, 'currency', 'USD'),
                'app.support_email' => Arr::get($values, 'support_email'),
                'asrtech.company_name' => Arr::get($values, 'company_name', config('asrtech.company_name')),
                'asrtech.tagline' => Arr::get($values, 'tagline', config('asrtech.tagline')),
                'asrtech.support_email' => Arr::get($values, 'support_email', config('asrtech.support_email')),
                'asrtech.phone' => Arr::get($values, 'phone', config('asrtech.phone')),
                'asrtech.address' => Arr::get($values, 'address', config('asrtech.address')),
                'asrtech.logo_url' => Arr::get($values, 'branding_logo_url') ?? Arr::get($values, 'logo_url', config('asrtech.logo_url')),
                'asrtech.currency' => Arr::get($values, 'currency', config('asrtech.currency')),
                'asrtech.social.facebook' => Arr::get($values, 'facebook_url', config('asrtech.social.facebook')),
                'asrtech.social.linkedin' => Arr::get($values, 'linkedin_url', config('asrtech.social.linkedin')),
                'asrtech.social.github' => Arr::get($values, 'github_url', config('asrtech.social.github')),
                'asrtech.seo.title' => Arr::get($values, 'default_meta_title', config('asrtech.seo.title')),
                'asrtech.seo.description' => Arr::get($values, 'default_meta_description', config('asrtech.seo.description')),
                'asrtech.seo.image' => Arr::get($values, 'default_og_image', config('asrtech.seo.image')),
                'asrtech.seo.home.title' => Arr::get($values, 'home_meta_title') ?: Arr::get($values, 'default_meta_title', config('asrtech.seo.title')),
                'asrtech.seo.home.description' => Arr::get($values, 'home_meta_description') ?: Arr::get($values, 'default_meta_description', config('asrtech.seo.description')),
                'asrtech.seo.home.keywords' => Arr::get($values, 'home_meta_keywords'),
                'asrtech.seo.home.image' => Arr::get($values, 'home_og_image') ?: Arr::get($values, 'default_og_image', config('asrtech.seo.image')),
                'asrtech.seo.verification.google' => Arr::get($values, 'google_site_verification'),
                'asrtech.seo.verification.bing' => Arr::get($values, 'bing_site_verification'),
                'asrtech.seo.verification.yandex' => Arr::get($values, 'yandex_site_verification'),
                'asrtech.seo.verification.baidu' => Arr::get($values, 'baidu_site_verification'),
                'asrtech.seo.verification.pinterest' => Arr::get($values, 'pinterest_site_verification'),
                'asrtech.analytics.ga4' => Arr::get($values, 'ga4_measurement_id'),
                'asrtech.analytics.gtm' => Arr::get($values, 'gtm_container_id'),
                'asrtech.analytics.meta_pixel' => Arr::get($values, 'meta_pixel_id'),
                'asrtech.allow_registration' => Arr::get($values, 'allow_registration', '1') === '1',
                'asrtech.require_tos_accept' => Arr::get($values, 'require_tos_accept', '0') === '1',
                'asrtech.terms_url' => Arr::get($values, 'terms_url'),
                'asrtech.date_format' => Arr::get($values, 'date_format', 'DD/MM/YYYY'),
                'asrtech.default_country' => Arr::get($values, 'default_country', 'US'),
                'asrtech.currency_symbol' => Arr::get($values, 'currency_symbol', '$'),
                'asrtech.invoice.number_prefix' => Arr::get($values, 'invoice_number_prefix', 'INV'),
                'asrtech.invoice.due_days' => (int) Arr::get($values, 'invoice_due_days', '14'),
                'asrtech.invoice.pay_to' => Arr::get($values, 'invoice_pay_to'),
                'asrtech.invoice.footer_note' => Arr::get($values, 'invoice_footer_note'),
                'asrtech.maintenance_mode' => Arr::get($values, 'maintenance_mode', '0') === '1',
                'asrtech.maintenance_message' => Arr::get($values, 'maintenance_message'),
                'asrtech.records_per_page' => (int) Arr::get($values, 'records_per_page', '15'),
                'asrtech.auto_provision_licenses' => Arr::get($values, 'auto_provision_licenses', '1') === '1',
                'asrtech.invoice.reminders_enabled' => Arr::get($values, 'send_invoice_reminders', '0') === '1',
                'asrtech.invoice.reminder_days' => (int) Arr::get($values, 'invoice_reminder_days', '7'),
                'asrtech.subscriptions.reminders_enabled' => Arr::get($values, 'send_subscription_reminders', '0') === '1',
                'asrtech.subscriptions.reminder_days' => (int) Arr::get($values, 'subscription_reminder_days', '7'),
                'asrtech.subscriptions.grace_days' => (int) Arr::get($values, 'subscription_grace_days', '3'),
                'asrtech.mail_signature' => Arr::get($values, 'mail_signature'),
                'asrtech.security.login_max_attempts' => (int) Arr::get($values, 'login_max_attempts', '6'),
                'asrtech.security.login_decay_minutes' => (int) Arr::get($values, 'login_decay_minutes', '1'),
                'asrtech.security.otp_ttl' => (int) Arr::get($values, 'email_otp_ttl_minutes', '10'),
                'asrtech.activity_log_limit' => (int) Arr::get($values, 'activity_log_limit', '1000'),
                'asrtech.mail_bcc' => Arr::get($values, 'mail_bcc'),
                'asrtech.email.header_html' => Arr::get($values, 'email_header_content'),
                'asrtech.email.footer_html' => Arr::get($values, 'email_footer_content'),
                'asrtech.logo_light_url' => Arr::get($values, 'branding_logo_light_url'),
                'asrtech.logo_dark_url' => Arr::get($values, 'branding_logo_dark_url'),
                'asrtech.favicon_url' => Arr::get($values, 'branding_favicon_url'),
            ]);

            if (filled(Arr::get($values, 'site_url'))) {
                config(['app.url' => Arr::get($values, 'site_url')]);
            }

            if (filled(Arr::get($values, 'mail_from_address'))) {
                config(['mail.from.address' => Arr::get($values, 'mail_from_address')]);
            }

            if (filled(Arr::get($values, 'mail_from_name'))) {
                config(['mail.from.name' => Arr::get($values, 'mail_from_name')]);
            }

            // Social sign-in credentials live in the configuration
            // table; env values remain a first-boot fallback.
            foreach (['google', 'github'] as $provider) {
                if (filled(Arr::get($values, "{$provider}_client_id"))) {
                    config([
                        "services.{$provider}.client_id" => Arr::get($values, "{$provider}_client_id"),
                        "services.{$provider}.client_secret" => Arr::get($values, "{$provider}_client_secret"),
                    ]);
                }
            }

            // Route mail through SMTP as soon as a host is configured.
            if (filled(Arr::get($values, 'smtp_host'))) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => Arr::get($values, 'smtp_host'),
                    'mail.mailers.smtp.port' => (int) Arr::get($values, 'smtp_port', '587'),
                    'mail.mailers.smtp.username' => Arr::get($values, 'smtp_username'),
                    'mail.mailers.smtp.password' => Arr::get($values, 'smtp_password'),
                ]);
            }

            // "Disable Email Sending": everything goes to the log only.
            if (Arr::get($values, 'mail_disable', '0') === '1') {
                config(['mail.default' => 'log', 'asrtech.mail_disabled' => true]);
            }

            // Upload storage: rebuild the `uploads` disk from the Storage
            // Settings page. Runs after the site_url override so the local
            // driver's URL follows the configured domain.
            config([
                'asrtech.storage.driver' => Arr::get($values, 'storage_driver', 'local'),
                'asrtech.storage.paths.branding' => Arr::get($values, 'storage_path_branding') ?: StorageService::DEFAULT_PATHS['branding'],
                'asrtech.storage.paths.tickets' => Arr::get($values, 'storage_path_tickets') ?: StorageService::DEFAULT_PATHS['tickets'],
                'asrtech.storage.paths.products' => Arr::get($values, 'storage_path_products') ?: StorageService::DEFAULT_PATHS['products'],
                'filesystems.disks.uploads' => StorageService::diskConfig($values),
            ]);

            date_default_timezone_set((string) config('app.timezone'));
        } catch (Throwable) {
            // Installation commands must remain available before migrations.
        }
    }
}
