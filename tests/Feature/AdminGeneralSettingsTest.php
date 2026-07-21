<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\EmailOtp;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Providers\SettingsServiceProvider;
use App\Services\EmailOtpService;
use App\Services\InvoiceService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminGeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_general_configuration(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'invoice_number_prefix' => 'ASR',
            'invoice_due_days' => 21,
            'smtp_password' => 'smtp-secret',
        ])->assertRedirect('/admin/settings/general');

        $settings = app(SettingService::class);
        $this->assertSame('ASR', $settings->get('invoice_number_prefix'));
        $this->assertSame('21', $settings->get('invoice_due_days'));
        $this->assertSame('smtp-secret', $settings->get('smtp_password'));
        $this->assertSame('1', $settings->get('send_subscription_reminders'));
        $this->assertSame('5', $settings->get('subscription_reminder_days'));
        $this->assertSame('3', $settings->get('subscription_grace_days'));

        $this->get('/admin/settings/general')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/General')
                ->where('settings.invoice_number_prefix', 'ASR')
                ->where('settings.send_subscription_reminders', true)
                ->where('settings.subscription_grace_days', 3)
                ->where('settings.smtp_password_configured', true)
                ->missing('settings.smtp_password'));
    }

    public function test_blank_smtp_password_keeps_the_stored_secret(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $settings = app(SettingService::class);
        $settings->put('smtp_password', 'existing-secret');

        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'smtp_password' => '',
        ])->assertRedirect('/admin/settings/general');

        $this->assertSame('existing-secret', $settings->get('smtp_password'));
    }

    public function test_disabling_registration_blocks_native_and_page_access(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'allow_registration' => false,
        ]);
        $this->rebootSettings();

        $this->post('/admin/logout');

        $this->get('/register')->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertForbidden();
    }

    public function test_tos_acceptance_is_enforced_when_enabled(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'require_tos_accept' => true,
            'terms_url' => 'https://example.com/terms',
        ]);
        $this->rebootSettings();

        $this->post('/admin/logout');

        $this->post('/register', [
            'name' => 'No Terms',
            'email' => 'noterms@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('terms');

        $this->post('/register', [
            'name' => 'With Terms',
            'email' => 'withterms@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'terms' => true,
        ])->assertRedirect('/verify-email');
    }

    public function test_general_configuration_requires_an_admin_session(): void
    {
        $this->get('/admin/settings/general')->assertRedirect('/admin/login');
    }

    public function test_disable_email_sending_routes_mail_to_the_log(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'mail_disable' => true,
        ]);
        $this->rebootSettings();

        $this->assertSame('log', config('mail.default'));
        $this->assertTrue((bool) config('asrtech.mail_disabled'));
    }

    public function test_admin_can_upload_branding_assets(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 80),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])->assertRedirect('/admin/settings/general');

        $settings = app(SettingService::class);
        $logoUrl = $settings->get('branding_logo_url');
        $faviconUrl = $settings->get('branding_favicon_url');

        $this->assertStringStartsWith('/storage/branding/', (string) $logoUrl);
        $this->assertStringStartsWith('/storage/branding/', (string) $faviconUrl);
        Storage::disk('uploads')
            ->assertExists(str_replace('/storage/', '', (string) $logoUrl));

        $this->rebootSettings();
        $this->assertSame($logoUrl, config('asrtech.logo_url'));
        $this->assertSame($faviconUrl, config('asrtech.favicon_url'));
    }

    public function test_settings_live_in_the_configuration_table_encrypted_at_rest(): void
    {
        $settings = app(SettingService::class);
        $settings->put('app_name', 'ASR Tech');

        $raw = DB::table('configuration')
            ->where('setting', 'app_name')
            ->value('value');

        $this->assertNotNull($raw);
        $this->assertNotSame('ASR Tech', $raw);
        $this->assertSame('ASR Tech', $settings->get('app_name'));
    }

    public function test_social_login_credentials_come_from_the_configuration_table(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'google_client_id' => 'google-client-123',
            'google_client_secret' => 'google-secret-456',
        ])->assertRedirect('/admin/settings/general');

        $this->rebootSettings();

        $this->assertSame('google-client-123', config('services.google.client_id'));
        $this->assertSame('google-secret-456', config('services.google.client_secret'));

        $this->post('/admin/logout');

        // The Google button now appears for visitors.
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('socialProviders', ['google']));

        // Secrets are never returned to the settings page.
        $this->actingAs($admin, 'admin');
        $this->get('/admin/settings/general')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.google_client_id', 'google-client-123')
                ->where('settings.google_client_secret_configured', true)
                ->missing('settings.google_client_secret'));
    }

    public function test_maintenance_mode_blocks_the_storefront_but_not_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->put('/admin/settings/general', [
            ...$this->validPayload(),
            'maintenance_mode' => true,
            'maintenance_message' => 'Upgrading — back soon.',
        ]);
        $this->rebootSettings();

        // Admin sessions bypass maintenance, including on the storefront.
        $this->get('/')->assertOk();
        $this->get('/admin/dashboard')->assertOk();

        $this->post('/admin/logout');

        $this->get('/')
            ->assertServiceUnavailable()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Maintenance')
                ->where('message', 'Upgrading — back soon.'));
    }

    public function test_auto_provisioning_can_be_disabled(): void
    {
        config(['asrtech.auto_provision_licenses' => false]);

        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Toolkit',
            'slug' => 'toolkit',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => 149,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}");

        $this->assertSame(0, $user->licenses()->count());
        // The invoice is still generated for the paid order.
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_otp_expiry_honors_the_configured_ttl(): void
    {
        config(['asrtech.security.otp_ttl' => 30]);

        $user = User::factory()->unverified()->create();
        app(EmailOtpService::class)->issue($user);

        $otp = EmailOtp::query()->sole();
        $this->assertTrue($otp->expires_at->between(
            now()->addMinutes(29),
            now()->addMinutes(31),
        ));
    }

    public function test_invoice_reminders_are_sent_once_per_invoice(): void
    {
        Mail::fake();
        config([
            'asrtech.invoice.reminders_enabled' => true,
            // Wider than the 14-day due window so the invoice qualifies.
            'asrtech.invoice.reminder_days' => 20,
        ]);

        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Toolkit',
            'slug' => 'toolkit',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
        $order = $user->orders()->create([
            'product_id' => $product->id,
            'order_number' => 'ORD-20260721-REM001',
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => 'pending',
        ]);
        app(InvoiceService::class)->createForOrder($order);

        $this->artisan('invoices:send-reminders')->assertSuccessful();
        Mail::assertSent(InvoiceMail::class, 1);

        // A second run does not re-send.
        $this->artisan('invoices:send-reminders')->assertSuccessful();
        Mail::assertSent(InvoiceMail::class, 1);
    }

    /**
     * Re-map settings into config the way a fresh request boot would —
     * the test application boots only once, before settings are saved.
     */
    private function rebootSettings(): void
    {
        (new SettingsServiceProvider($this->app))->boot(app(SettingService::class));
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'site_url' => 'https://asrtech.example',
            'company_name' => 'ASR Tech Ltd',
            'app_name' => 'ASRTech',
            'support_email' => null,
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
            'send_subscription_reminders' => true,
            'subscription_reminder_days' => 5,
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
            'invoice_pay_to' => "ASR Tech Ltd\nDhaka, Bangladesh",
            'invoice_footer_note' => 'Thank you for your business.',
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
            'name' => 'Config Admin',
            'email' => 'config@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
