<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Providers\SettingsServiceProvider;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_global_seo_settings(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings/seo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/Seo')
                ->has('settings'));
    }

    public function test_admin_can_save_verification_analytics_and_home_seo(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/seo', [
            ...$this->validPayload(),
            'google_site_verification' => 'google-token-123',
            // A full pasted meta tag is reduced to its content value.
            'bing_site_verification' => '<meta name="msvalidate.01" content="bing-token-456" />',
            'ga4_measurement_id' => 'G-ABC1234567',
            'gtm_container_id' => 'GTM-ABC1234',
            'meta_pixel_id' => '123456789012345',
            'home_meta_title' => 'ASRTech — WHMCS Modules & Templates',
            'home_meta_keywords' => 'whmcs, modules, templates',
        ])->assertRedirect('/admin/settings/seo');

        $settings = app(SettingService::class);
        $this->assertSame('google-token-123', $settings->get('google_site_verification'));
        $this->assertSame('bing-token-456', $settings->get('bing_site_verification'));
        $this->assertSame('G-ABC1234567', $settings->get('ga4_measurement_id'));
        $this->assertSame('GTM-ABC1234', $settings->get('gtm_container_id'));

        (new SettingsServiceProvider($this->app))->boot($settings);
        $this->assertSame('google-token-123', config('asrtech.seo.verification.google'));
        $this->assertSame('bing-token-456', config('asrtech.seo.verification.bing'));
        $this->assertSame('G-ABC1234567', config('asrtech.analytics.ga4'));
        $this->assertSame('ASRTech — WHMCS Modules & Templates', config('asrtech.seo.home.title'));
        // Blank home description falls back to the site default.
        $this->assertSame('Default description', config('asrtech.seo.home.description'));
    }

    public function test_verification_and_analytics_tags_render_on_the_storefront_only(): void
    {
        $settings = app(SettingService::class);
        $settings->put('google_site_verification', 'google-token-123');
        $settings->put('ga4_measurement_id', 'G-ABC1234567');
        $settings->put('gtm_container_id', 'GTM-ABC1234');
        $settings->put('meta_pixel_id', '123456789012345');
        (new SettingsServiceProvider($this->app))->boot($settings);

        $storefront = $this->get('/');
        $storefront->assertOk();
        $storefront->assertSee('name="google-site-verification" content="google-token-123"', false);
        $storefront->assertSee('gtag/js?id=G-ABC1234567', false);
        $storefront->assertSee('GTM-ABC1234', false);
        $storefront->assertSee('fbq(\'init\', \'123456789012345\')', false);

        $this->actingAs($this->admin(), 'admin');
        $adminPage = $this->get('/admin/dashboard');
        $adminPage->assertOk();
        // Verification metas are harmless everywhere; tracking scripts are not.
        $adminPage->assertDontSee('gtag/js', false);
        $adminPage->assertDontSee('googletagmanager.com/gtm.js', false);
        $adminPage->assertDontSee('fbevents.js', false);
    }

    public function test_invalid_tracking_ids_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/seo', [
            ...$this->validPayload(),
            'ga4_measurement_id' => 'UA-12345-1',
            'gtm_container_id' => 'not-a-container',
            'meta_pixel_id' => 'pixel-abc',
        ])->assertSessionHasErrors([
            'ga4_measurement_id',
            'gtm_container_id',
            'meta_pixel_id',
        ]);
    }

    public function test_admin_can_upload_and_remove_og_images(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/seo', [
            ...$this->validPayload(),
            'og_image' => UploadedFile::fake()->image('og.png', 1200, 630),
            'home_og_image' => UploadedFile::fake()->image('home-og.png', 1200, 630),
        ])->assertRedirect('/admin/settings/seo');

        $settings = app(SettingService::class);
        $ogUrl = (string) $settings->get('default_og_image');
        $homeOgUrl = (string) $settings->get('home_og_image');

        $this->assertStringStartsWith('/storage/branding/', $ogUrl);
        $this->assertStringStartsWith('/storage/branding/', $homeOgUrl);
        Storage::disk('uploads')->assertExists(str_replace('/storage/', '', $ogUrl));

        (new SettingsServiceProvider($this->app))->boot($settings);
        $this->assertSame($ogUrl, config('asrtech.seo.image'));
        $this->assertSame($homeOgUrl, config('asrtech.seo.home.image'));

        // Remove the home override; it falls back to the default image.
        $this->put('/admin/settings/seo', [
            ...$this->validPayload(),
            'remove_home_og_image' => true,
        ])->assertRedirect('/admin/settings/seo');

        $this->assertNull($settings->get('home_og_image'));
        (new SettingsServiceProvider($this->app))->boot($settings);
        $this->assertSame($ogUrl, config('asrtech.seo.home.image'));
    }

    public function test_home_page_uses_home_seo_overrides(): void
    {
        $settings = app(SettingService::class);
        $settings->put('home_meta_title', 'Custom Home Title');
        $settings->put('home_meta_keywords', 'whmcs, modules');
        (new SettingsServiceProvider($this->app))->boot($settings);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Client/Home'));

        $this->assertSame('Custom Home Title', config('asrtech.seo.home.title'));
        $this->assertSame('whmcs, modules', config('asrtech.seo.home.keywords'));
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'default_meta_title' => 'ASRTech Products',
            'default_meta_description' => 'Default description',
            'home_meta_title' => null,
            'home_meta_description' => null,
            'home_meta_keywords' => null,
            'google_site_verification' => null,
            'bing_site_verification' => null,
            'yandex_site_verification' => null,
            'baidu_site_verification' => null,
            'pinterest_site_verification' => null,
            'ga4_measurement_id' => null,
            'gtm_container_id' => null,
            'meta_pixel_id' => null,
        ];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'SEO Admin',
            'email' => 'seo@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
