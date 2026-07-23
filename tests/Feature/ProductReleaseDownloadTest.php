<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductReleaseDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_private_release_and_product_version_is_synchronized(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product();

        $this->post("/admin/products/{$product->id}/releases", [
            'version' => '2.5.0',
            'title' => 'Secure delivery',
            'release_notes' => 'Adds signed package delivery.',
            'released_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'available_until' => now()->addWeek()->format('Y-m-d H:i:s'),
            'download_limit' => 3,
            'status' => true,
            'release_file' => UploadedFile::fake()->create('toolkit.zip', 128, 'application/zip'),
        ])->assertRedirect("/admin/products/{$product->id}/releases");

        $release = ProductRelease::query()->sole();

        Storage::disk('local')->assertExists($release->file_path);
        $this->assertSame('toolkit.zip', $release->original_filename);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $release->checksum_sha256);
        $this->assertSame('2.5.0', $product->fresh()->version);
        $this->assertDatabaseHas('product_releases', [
            'product_id' => $product->id,
            'download_limit' => 3,
            'status' => true,
        ]);
    }

    public function test_admin_can_replace_and_delete_a_release_package(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product();
        $release = $this->release($product);
        $oldPath = $release->file_path;

        $this->put("/admin/products/{$product->id}/releases/{$release->id}", [
            'version' => '2.5.1',
            'title' => 'Replacement package',
            'release_notes' => null,
            'released_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'available_until' => null,
            'download_limit' => null,
            'status' => true,
            'release_file' => UploadedFile::fake()->create('toolkit-2.5.1.zip', 64, 'application/zip'),
        ])->assertRedirect("/admin/products/{$product->id}/releases");

        $release->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($release->file_path);
        $this->assertSame('toolkit-2.5.1.zip', $release->original_filename);

        $newPath = $release->file_path;
        $this->delete("/admin/products/{$product->id}/releases/{$release->id}")
            ->assertRedirect("/admin/products/{$product->id}/releases");

        Storage::disk('local')->assertMissing($newPath);
        $this->assertDatabaseMissing('product_releases', ['id' => $release->id]);
        $this->assertNull($product->fresh()->version);
    }

    public function test_client_product_page_lists_only_current_published_releases(): void
    {
        $this->withoutVite();
        Storage::fake('local');
        [$user, $product, $license] = $this->licensedProduct();
        $current = $this->release($product, ['version' => '2.5.0', 'download_limit' => 2]);
        $this->release($product, ['version' => '2.6.0', 'released_at' => now()->addDay()]);
        $this->release($product, ['version' => '2.4.0', 'status' => false]);

        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Product')
                ->has('releases', 1)
                ->where('releases.0.id', $current->id)
                ->where('releases.0.version', '2.5.0')
                ->where('releases.0.downloads_remaining', 2)
                ->where('releases.0.can_download', true)
                ->missing('releases.0.file_path')
                ->missing('releases.0.disk'));
    }

    public function test_license_owner_can_download_and_each_attempt_is_audited_and_limited(): void
    {
        Storage::fake('local');
        [$user, $product, $license] = $this->licensedProduct();
        $release = $this->release($product, ['download_limit' => 1]);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'ASRTech test client')
            ->get("/client-area/product/{$license->id}/releases/{$release->id}/download")
            ->assertOk()
            ->assertDownload('toolkit.zip')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');

        $this->assertDatabaseHas('product_release_downloads', [
            'product_release_id' => $release->id,
            'license_id' => $license->id,
            'user_id' => $user->id,
            'user_agent' => 'ASRTech test client',
        ]);

        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}/releases/{$release->id}/download")
            ->assertForbidden();
        $this->assertDatabaseCount('product_release_downloads', 1);
    }

    public function test_download_is_private_to_the_license_owner_and_matching_product(): void
    {
        Storage::fake('local');
        [$user, $product, $license] = $this->licensedProduct();
        $release = $this->release($product);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("/client-area/product/{$license->id}/releases/{$release->id}/download")
            ->assertNotFound();

        $otherRelease = $this->release($this->product('other-product'), ['version' => '1.0.0']);
        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}/releases/{$otherRelease->id}/download")
            ->assertNotFound();

        $this->assertDatabaseCount('product_release_downloads', 0);
    }

    public function test_suspended_expired_future_and_unpublished_downloads_are_rejected(): void
    {
        Storage::fake('local');
        [$user, $product, $license] = $this->licensedProduct();
        $current = $this->release($product);

        $license->update(['status' => LicenseStatus::Suspended]);
        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}/releases/{$current->id}/download")
            ->assertForbidden();

        $license->update(['status' => LicenseStatus::Active, 'expires_at' => now()->subMinute()]);
        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}/releases/{$current->id}/download")
            ->assertForbidden();

        $license->update(['expires_at' => null]);
        foreach ([
            $this->release($product, ['version' => '3.0.0', 'released_at' => now()->addDay()]),
            $this->release($product, ['version' => '3.1.0', 'status' => false]),
            $this->release($product, ['version' => '3.2.0', 'available_until' => now()->subMinute()]),
        ] as $unavailable) {
            $this->actingAs($user)
                ->get("/client-area/product/{$license->id}/releases/{$unavailable->id}/download")
                ->assertNotFound();
        }

        $this->assertDatabaseCount('product_release_downloads', 0);
    }

    /** @return array{User, Product, License} */
    private function licensedProduct(): array
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $license = License::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'license_key' => 'ASR-'.fake()->unique()->regexify('[A-Z]{5}-[A-Z]{5}-[A-Z]{5}'),
            'status' => LicenseStatus::Active,
        ]);

        return [$user, $product, $license];
    }

    private function product(string $slug = 'automation-toolkit'): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => str($slug)->headline(),
            'slug' => $slug,
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function release(Product $product, array $overrides = []): ProductRelease
    {
        $attributes = [
            'version' => '2.5.0',
            'title' => 'Stable release',
            'release_notes' => 'Production-ready package.',
            'disk' => 'local',
            'file_path' => "product-releases/{$product->id}/toolkit.zip",
            'original_filename' => 'toolkit.zip',
            'mime_type' => 'application/zip',
            'file_size' => 15,
            'checksum_sha256' => hash('sha256', 'release-package'),
            'released_at' => now()->subMinute(),
            'available_until' => null,
            'download_limit' => null,
            'status' => true,
            ...$overrides,
        ];

        $release = $product->releases()->create($attributes);
        Storage::disk('local')->put($release->file_path, 'release-package');

        return $release;
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Release Admin',
            'email' => 'releases@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
