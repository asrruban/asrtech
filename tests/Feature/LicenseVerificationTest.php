<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseAccessLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_activation_records_the_installation(): void
    {
        $license = $this->makeLicense();

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'ClientSite.com',
            'ip' => '203.0.113.10',
            'path' => '/home/client/public_html',
        ])->assertOk()
            ->assertJson([
                'status' => 'active',
                'message' => 'Valid (Installation Recorded)',
                'product' => 'Automation Toolkit',
            ]);

        $license->refresh();
        $this->assertSame('clientsite.com', $license->domain);
        $this->assertSame('203.0.113.10', $license->ip_address);
        $this->assertSame(1, $license->accessLogs()->count());
    }

    public function test_checks_from_the_recorded_domain_stay_valid(): void
    {
        $license = $this->makeLicense(['domain' => 'clientsite.com']);

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'clientsite.com',
        ])->assertOk()->assertJson(['status' => 'active', 'message' => 'Valid']);
    }

    public function test_checks_from_another_domain_are_rejected_and_logged(): void
    {
        $license = $this->makeLicense(['domain' => 'clientsite.com']);

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'pirate-site.com',
        ])->assertOk()->assertJson(['status' => 'invalid', 'message' => 'Domain Invalid']);

        $this->assertSame('Domain Invalid', $license->accessLogs()->sole()->result);
    }

    public function test_ip_and_directory_mismatches_are_rejected(): void
    {
        $license = $this->makeLicense([
            'domain' => 'clientsite.com',
            'ip_address' => '203.0.113.10',
            'path' => '/home/client/public_html',
        ]);

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'clientsite.com',
            'ip' => '198.51.100.99',
        ])->assertOk()->assertJson(['message' => 'IP Address Invalid']);

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'clientsite.com',
            'ip' => '203.0.113.10',
            'path' => '/tmp/elsewhere',
        ])->assertOk()->assertJson(['message' => 'Directory Invalid']);
    }

    public function test_multiple_valid_domains_are_supported(): void
    {
        $license = $this->makeLicense([
            'domain' => 'clientsite.com,www.clientsite.com',
        ]);

        $this->postJson('/api/license/verify', [
            'license_key' => $license->license_key,
            'domain' => 'www.clientsite.com',
        ])->assertOk()->assertJson(['status' => 'active']);
    }

    public function test_suspended_terminated_and_expired_licenses_fail_checks(): void
    {
        $suspended = $this->makeLicense(['status' => LicenseStatus::Suspended], 'ASR-SUSPE-NDED1-TEST1');
        $terminated = $this->makeLicense(['status' => LicenseStatus::Terminated], 'ASR-TERMI-NATED-TEST1');
        $expired = $this->makeLicense(['expires_at' => now()->subDay()], 'ASR-EXPIR-EDDD1-TEST1');

        $this->postJson('/api/license/verify', ['license_key' => $suspended->license_key])
            ->assertJson(['status' => 'suspended', 'message' => 'License Suspended']);
        $this->postJson('/api/license/verify', ['license_key' => $terminated->license_key])
            ->assertJson(['status' => 'terminated', 'message' => 'License Terminated']);
        $this->postJson('/api/license/verify', ['license_key' => $expired->license_key])
            ->assertJson(['status' => 'expired', 'message' => 'License Expired']);
    }

    public function test_unknown_keys_are_invalid_and_not_logged(): void
    {
        $this->postJson('/api/license/verify', ['license_key' => 'ASR-NOPE1-NOPE2-NOPE3'])
            ->assertOk()
            ->assertJson(['status' => 'invalid', 'message' => 'Invalid License Key']);

        $this->assertSame(0, LicenseAccessLog::query()->count());
    }

    public function test_access_log_is_pruned_to_the_configured_limit(): void
    {
        config(['asrtech.activity_log_limit' => 2]);

        $license = $this->makeLicense(['domain' => 'clientsite.com']);

        foreach (range(1, 4) as $attempt) {
            $this->postJson('/api/license/verify', [
                'license_key' => $license->license_key,
                'domain' => 'clientsite.com',
            ]);
        }

        $this->assertSame(2, $license->accessLogs()->count());
    }

    public function test_clients_can_reissue_their_own_license(): void
    {
        $license = $this->makeLicense(['domain' => 'clientsite.com']);

        $this->actingAs($license->user)
            ->post("/client-area/licenses/{$license->id}/reissue")
            ->assertRedirect('/client-area');

        $license->refresh();
        $this->assertNull($license->domain);
        $this->assertSame(1, $license->reissue_count);
    }

    public function test_clients_cannot_reissue_someone_elses_license(): void
    {
        $license = $this->makeLicense(['domain' => 'clientsite.com']);

        $this->actingAs(User::factory()->create())
            ->post("/client-area/licenses/{$license->id}/reissue")
            ->assertForbidden();

        $this->assertSame('clientsite.com', $license->fresh()->domain);
    }

    /** @param array<string, mixed> $attributes */
    private function makeLicense(array $attributes = [], string $key = 'ASR-TESTA-TESTB-TESTC'): License
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->firstOrCreate(
            ['slug' => 'automation-toolkit'],
            [
                'category_id' => $category->id,
                'name' => 'Automation Toolkit',
                'type' => 'whmcs_module',
                'price' => 149,
                'status' => true,
                'featured' => false,
            ],
        );
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-'.strtoupper(substr(md5($key), 0, 12)),
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return License::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'license_key' => $key,
            'status' => LicenseStatus::Active,
            ...$attributes,
        ]);
    }
}
