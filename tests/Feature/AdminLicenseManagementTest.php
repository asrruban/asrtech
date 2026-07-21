<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminLicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_license_detail_page(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $license = $this->makeLicense();

        $this->get("/admin/licenses/{$license->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Licenses/Show')
                ->where('license.license_key', $license->license_key)
                ->where('license.user.email', $license->user->email)
                ->where('license.order.order_number', 'ORD-20260721-LIC001'));
    }

    public function test_license_detail_page_requires_an_admin_session(): void
    {
        $license = $this->makeLicense();

        $this->get("/admin/licenses/{$license->id}")->assertRedirect('/admin/login');
    }

    public function test_admin_can_suspend_and_unsuspend_a_license(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $license = $this->makeLicense();

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'suspend'])
            ->assertRedirect("/admin/licenses/{$license->id}");
        $this->assertSame(LicenseStatus::Suspended, $license->fresh()->status);

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'unsuspend']);
        $this->assertSame(LicenseStatus::Active, $license->fresh()->status);
    }

    public function test_admin_can_terminate_a_license_and_it_stays_terminated(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $license = $this->makeLicense();

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'terminate']);
        $this->assertSame(LicenseStatus::Terminated, $license->fresh()->status);

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'suspend'])
            ->assertSessionHasErrors('action');
        $this->assertSame(LicenseStatus::Terminated, $license->fresh()->status);
    }

    public function test_admin_can_record_and_reissue_an_installation(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $license = $this->makeLicense();

        $this->patch("/admin/licenses/{$license->id}", [
            'action' => 'update_installation',
            'domain' => 'clientsite.com',
            'path' => '/home/client/public_html/whmcs',
            'ip_address' => '203.0.113.10',
        ]);

        $license->refresh();
        $this->assertSame('clientsite.com', $license->domain);
        $this->assertSame('/home/client/public_html/whmcs', $license->path);
        $this->assertSame('203.0.113.10', $license->ip_address);

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'reissue']);

        $license->refresh();
        $this->assertNull($license->domain);
        $this->assertNull($license->path);
        $this->assertNull($license->ip_address);
        $this->assertSame(LicenseStatus::Active, $license->status);
    }

    public function test_invalid_ip_addresses_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $license = $this->makeLicense();

        $this->patch("/admin/licenses/{$license->id}", [
            'action' => 'update_installation',
            'domain' => 'clientsite.com',
            'ip_address' => 'not-an-ip',
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_license_actions_require_an_admin_session(): void
    {
        $license = $this->makeLicense();

        $this->patch("/admin/licenses/{$license->id}", ['action' => 'suspend'])
            ->assertRedirect('/admin/login');

        $this->actingAs($license->user)
            ->patch("/admin/licenses/{$license->id}", ['action' => 'suspend'])
            ->assertRedirect('/admin/login');
    }

    private function makeLicense(): License
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Automation Toolkit',
            'slug' => 'automation-toolkit',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-20260721-LIC001',
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
            'license_key' => 'ASR-TESTA-TESTB-TESTC',
            'status' => LicenseStatus::Active,
        ]);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'License Admin',
            'email' => 'licenses@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
