<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Providers\SettingsServiceProvider;
use App\Services\SettingService;
use App\Services\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStorageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_storage_settings(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings/storage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/Storage')
                ->where('settings.storage_driver', 'local')
                ->where('settings.storage_path_branding', 'branding')
                ->where('settings.storage_path_tickets', 'support/tickets')
                ->where('settings.storage_path_products', 'products')
                ->has('drivers'));
    }

    public function test_admin_can_save_local_driver_with_custom_paths(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_path_branding' => '/brand-assets/',
            'storage_path_products' => 'catalog/images',
        ])->assertRedirect('/admin/settings/storage');

        $settings = app(SettingService::class);
        // Leading and trailing slashes are normalized away.
        $this->assertSame('brand-assets', $settings->get('storage_path_branding'));
        $this->assertSame('catalog/images', $settings->get('storage_path_products'));

        $this->rebootSettings();
        $this->assertSame('brand-assets', config('asrtech.storage.paths.branding'));
        $this->assertSame('local', config('filesystems.disks.uploads.driver'));
    }

    public function test_cloud_driver_requires_its_credentials(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_driver' => 's3',
        ])->assertSessionHasErrors([
            'storage_s3_key',
            'storage_s3_region',
            'storage_s3_bucket',
        ]);

        // With the text fields present but no secret ever stored, the
        // write-only secret is still required.
        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_driver' => 's3',
            'storage_s3_key' => 'AKIAEXAMPLE',
            'storage_s3_region' => 'us-east-1',
            'storage_s3_bucket' => 'asrtech-uploads',
        ])->assertSessionHasErrors(['storage_s3_secret']);
    }

    public function test_admin_can_save_cloud_driver_and_secret_is_write_only(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_driver' => 'r2',
            'storage_r2_account_id' => 'abc123def456',
            'storage_r2_key' => 'r2-access-key',
            'storage_r2_secret' => 'r2-secret-key',
            'storage_r2_bucket' => 'asrtech-files',
            'storage_r2_url' => 'https://files.asrtech.example',
        ])->assertRedirect('/admin/settings/storage');

        $settings = app(SettingService::class);
        $this->assertSame('r2', $settings->get('storage_driver'));
        $this->assertSame('r2-secret-key', $settings->get('storage_r2_secret'));

        // Saving again with a blank secret keeps the stored one.
        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_driver' => 'r2',
            'storage_r2_account_id' => 'abc123def456',
            'storage_r2_key' => 'r2-access-key',
            'storage_r2_secret' => '',
            'storage_r2_bucket' => 'asrtech-files',
            'storage_r2_url' => 'https://files.asrtech.example',
        ])->assertRedirect('/admin/settings/storage');
        $this->assertSame('r2-secret-key', $settings->get('storage_r2_secret'));

        // The uploads disk is rebuilt as S3-compatible R2 at boot, and the
        // secret never travels back to the page.
        $this->rebootSettings();
        $this->assertSame('s3', config('filesystems.disks.uploads.driver'));
        $this->assertSame(
            'https://abc123def456.r2.cloudflarestorage.com',
            config('filesystems.disks.uploads.endpoint'),
        );

        $this->get('/admin/settings/storage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.storage_r2_secret_configured', true)
                ->missing('settings.storage_r2_secret'));
    }

    public function test_branding_uploads_honor_the_configured_path(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/storage', [
            ...$this->localPayload(),
            'storage_path_branding' => 'brand-assets',
        ])->assertRedirect('/admin/settings/storage');
        $this->rebootSettings();

        $this->post('/admin/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 80),
        ])->assertRedirect('/admin/settings/general');

        $logoUrl = (string) app(SettingService::class)->get('branding_logo_url');
        $this->assertStringStartsWith('/storage/brand-assets/', $logoUrl);
        Storage::disk('uploads')
            ->assertExists(str_replace('/storage/', '', $logoUrl));
    }

    public function test_disk_config_maps_each_driver_to_its_endpoint(): void
    {
        $s3 = StorageService::diskConfig([
            'storage_driver' => 's3',
            'storage_s3_key' => 'k',
            'storage_s3_secret' => 's',
            'storage_s3_region' => 'eu-west-1',
            'storage_s3_bucket' => 'b',
        ]);
        $this->assertSame('s3', $s3['driver']);
        $this->assertArrayNotHasKey('endpoint', $s3);

        $b2 = StorageService::diskConfig([
            'storage_driver' => 'b2',
            'storage_b2_key_id' => 'k',
            'storage_b2_key' => 's',
            'storage_b2_region' => 'us-west-004',
            'storage_b2_bucket' => 'b',
        ]);
        $this->assertSame('https://s3.us-west-004.backblazeb2.com', $b2['endpoint']);
        $this->assertTrue($b2['use_path_style_endpoint']);

        $local = StorageService::diskConfig([]);
        $this->assertSame('local', $local['driver']);
    }

    /** @return array<string, mixed> */
    private function localPayload(): array
    {
        return [
            'storage_driver' => 'local',
            'storage_s3_key' => null,
            'storage_s3_secret' => null,
            'storage_s3_region' => null,
            'storage_s3_bucket' => null,
            'storage_s3_url' => null,
            'storage_r2_account_id' => null,
            'storage_r2_key' => null,
            'storage_r2_secret' => null,
            'storage_r2_bucket' => null,
            'storage_r2_url' => null,
            'storage_b2_key_id' => null,
            'storage_b2_key' => null,
            'storage_b2_region' => null,
            'storage_b2_bucket' => null,
            'storage_b2_url' => null,
            'storage_path_branding' => 'branding',
            'storage_path_tickets' => 'support/tickets',
            'storage_path_products' => 'products',
        ];
    }

    private function rebootSettings(): void
    {
        (new SettingsServiceProvider($this->app))->boot(app(SettingService::class));
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Storage Admin',
            'email' => 'storage@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
