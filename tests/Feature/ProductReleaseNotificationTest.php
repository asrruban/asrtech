<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Jobs\SendProductReleaseNotifications;
use App\Mail\ProductReleasePublishedMail;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\User;
use App\Services\ProductReleaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductReleaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_release_notification_is_queued_only_once(): void
    {
        Queue::fake();
        $release = $this->release($this->product());
        $service = app(ProductReleaseNotificationService::class);

        $this->assertTrue($service->schedule($release));
        $this->assertFalse($service->schedule($release));

        Queue::assertPushed(
            SendProductReleaseNotifications::class,
            1,
        );
        $this->assertNotNull($release->fresh()->notification_queued_at);
    }

    public function test_release_email_is_queued_once_per_eligible_license_owner(): void
    {
        Mail::fake();
        $product = $this->product();
        $eligible = User::factory()->create(['email' => 'eligible@example.com']);
        $duplicateLicenseOwner = $eligible;
        $suspended = User::factory()->create(['email' => 'suspended@example.com']);
        $expired = User::factory()->create(['email' => 'expired@example.com']);

        $this->license($eligible, $product);
        $this->license($duplicateLicenseOwner, $product);
        $this->license($suspended, $product, LicenseStatus::Suspended);
        $this->license($expired, $product, LicenseStatus::Active, now()->subDay());
        $release = $this->release($product, ['notification_queued_at' => now()]);

        (new SendProductReleaseNotifications($release->id))->handle();

        Mail::assertQueued(
            ProductReleasePublishedMail::class,
            fn (ProductReleasePublishedMail $mail): bool => $mail->hasTo('eligible@example.com')
                && $mail->release->is($release),
        );
        Mail::assertQueued(ProductReleasePublishedMail::class, 1);
        Mail::assertNotQueued(
            ProductReleasePublishedMail::class,
            fn (ProductReleasePublishedMail $mail): bool => $mail->hasTo('suspended@example.com')
                || $mail->hasTo('expired@example.com'),
        );
        $this->assertNotNull($release->fresh()->notified_at);
    }

    public function test_future_release_is_picked_up_only_after_it_becomes_due(): void
    {
        Queue::fake();
        $release = $this->release($this->product(), [
            'released_at' => now()->addHour(),
        ]);
        $service = app(ProductReleaseNotificationService::class);

        $this->assertFalse($service->schedule($release));
        $this->assertSame(0, $service->scheduleDue());
        Queue::assertNothingPushed();

        $release->update(['released_at' => now()->subMinute()]);

        $this->assertSame(1, $service->scheduleDue());
        Queue::assertPushed(SendProductReleaseNotifications::class, 1);
    }

    public function test_release_email_template_contains_customer_download_link(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['name' => 'Release Customer']);
        $license = $this->license($user, $product);
        $release = $this->release($product, [
            'version' => '2.0.0',
            'release_notes' => 'Added safer transaction processing.',
        ]);
        $mail = new ProductReleasePublishedMail($release, $user, $license);

        $this->assertStringContainsString('2.0.0', (string) $mail->envelope()->subject);
        $this->assertStringContainsString(
            route('account.product', $license),
            $mail->render(),
        );
    }

    private function product(): Product
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Release Product',
            'slug' => 'release-product',
            'type' => 'whmcs_module',
            'price' => 99,
            'status' => true,
            'featured' => false,
        ]);
    }

    private function license(
        User $user,
        Product $product,
        LicenseStatus $status = LicenseStatus::Active,
        mixed $expiresAt = null,
    ): License {
        $order = Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'currency' => 'USD',
            'amount' => 99,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return License::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'license_key' => 'ASR-'.fake()->unique()->regexify('[A-Z]{5}-[A-Z]{5}-[A-Z]{5}'),
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function release(Product $product, array $overrides = []): ProductRelease
    {
        return $product->releases()->create([
            'version' => '1.0.0',
            'title' => 'New release',
            'release_notes' => 'Performance and reliability improvements.',
            'disk' => 'local',
            'file_path' => 'product-releases/package.zip',
            'original_filename' => 'package.zip',
            'mime_type' => 'application/zip',
            'file_size' => 100,
            'checksum_sha256' => hash('sha256', 'package'),
            'released_at' => now()->subMinute(),
            'available_until' => null,
            'download_limit' => null,
            'status' => true,
            ...$overrides,
        ]);
    }
}
