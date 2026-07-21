<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminClientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_profile_includes_invoices_and_billing_stats(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product, $price] = $this->makeProduct();

        app(CheckoutService::class)->manual($user, $product, $price, markPaid: true);

        $this->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->has('invoices', 1)
                ->where('billing.paid_count', 1)
                ->where('billing.paid_total', 149)
                ->where('billing.unpaid_count', 0)
                ->where('billing.gross_revenue', 149));
    }

    public function test_profile_tabs_are_addressable_urls(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();

        $this->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'summary'));

        $this->get("/admin/users/{$user->id}/invoices")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'invoices'));

        $this->get("/admin/users/{$user->id}/profile")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'profile'));

        $this->get("/admin/users/{$user->id}/nonsense")->assertNotFound();
    }

    public function test_admin_can_update_a_clients_profile(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this->patch("/admin/users/{$user->id}", [
            'name' => 'Renamed Client',
            'company_name' => 'BrodyBox Ltd',
            'email' => 'renamed@example.com',
            'phone' => '+8801000000000',
            'address_1' => '294 Park Valley',
            'address_2' => 'Suite 12',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1207',
            'country' => 'BD',
            'password' => '',
            'verified' => false,
        ])->assertRedirect("/admin/users/{$user->id}/profile");

        $user->refresh();
        $this->assertSame('Renamed Client', $user->name);
        $this->assertSame('BrodyBox Ltd', $user->company_name);
        $this->assertSame('renamed@example.com', $user->email);
        $this->assertSame('294 Park Valley', $user->address_1);
        $this->assertSame('Dhaka', $user->city);
        $this->assertSame('BD', $user->country);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_admin_can_set_a_new_password_for_a_client(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();

        $this->patch("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'verified' => true,
        ]);

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_profile_email_must_stay_unique(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->patch("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $other->email,
            'password' => '',
            'verified' => true,
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_save_notes_about_a_client(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();

        $this->patch("/admin/users/{$user->id}/notes", [
            'admin_notes' => 'VIP customer — priority support.',
        ])->assertRedirect("/admin/users/{$user->id}");

        $this->assertSame('VIP customer — priority support.', $user->fresh()->admin_notes);
    }

    public function test_profile_updates_require_an_admin_session(): void
    {
        $user = User::factory()->create();

        $this->patch("/admin/users/{$user->id}", [])->assertRedirect('/admin/login');
        $this->patch("/admin/users/{$user->id}/notes", [])->assertRedirect('/admin/login');
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function makeProduct(): array
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
        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => 149,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        return [$product, $price];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Profile Admin',
            'email' => 'profile-admin@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
