<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectInquiry;
use App\Models\User;
use App\Services\MaintenancePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsiteRouteCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        Notification::fake();
        Queue::fake();
    }

    /**
     * Navigation destinations must render for a new verified customer, including empty states.
     * Resource-specific ownership and mutation flows are covered by the existing feature suites.
     */
    public function test_all_customer_navigation_destinations_render_with_an_empty_account(): void
    {
        Mail::fake();
        Notification::fake();
        $this->actingAs(User::factory()->create());

        foreach ([
            '/dashboard' => 'Dashboard',
            '/client-area' => 'Client/Account/Index',
            '/client-area/products' => 'Client/Account/Products',
            '/client-area/subscriptions' => 'Client/Account/Subscriptions',
            '/client-area/invoices' => 'Client/Account/Invoices',
            '/client-area/quotes' => 'Client/Account/Quotes',
            '/client-area/projects' => 'Client/Projects/Index',
            '/client-area/maintenance' => 'Client/Account/Maintenance/Index',
            '/client-area/affiliate' => 'Client/Account/Affiliate',
            '/client-area/tickets' => 'Client/Support/Index',
            '/client-area/tickets/create' => 'Client/Support/Create',
            '/client-area/account-details' => 'Client/Account/Details',
            '/client-area/change-password' => 'Client/Account/ChangePassword',
            '/client-area/security' => 'Client/Account/Security',
            '/client-area/notifications' => 'Client/Account/Notifications',
            '/settings/appearance' => 'settings/Appearance',
        ] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_all_administration_navigation_and_create_screens_render(): void
    {
        Mail::fake();
        Notification::fake();
        $this->actingAs(Admin::query()->create([
            'name' => 'Preview administrator',
            'email' => 'preview-admin@example.test',
            'password' => 'test-only-password',
            'role' => AdminRole::SuperAdmin,
        ]), 'admin');

        foreach ([
            '/admin/dashboard', '/admin/products', '/admin/products/create',
            '/admin/product-types', '/admin/categories', '/admin/subcategories',
            '/admin/product-reviews', '/admin/users', '/admin/payments',
            '/admin/reports', '/admin/invoices', '/admin/quotes', '/admin/affiliates',
            '/admin/subscriptions', '/admin/refund-requests', '/admin/promotions',
            '/admin/tax-rates', '/admin/pages', '/admin/pages/create',
            '/admin/announcements', '/admin/docs', '/admin/security',
            '/admin/support/tickets', '/admin/support/departments',
            '/admin/support/departments/create', '/admin/inquiries',
            '/admin/projects', '/admin/maintenance/plans', '/admin/maintenance/requests',
            '/admin/settings/inquiry-notifications',
            '/admin/settings/general', '/admin/settings/gateways',
            '/admin/settings/emailtemplates', '/admin/settings/storage',
            '/admin/settings/seo', '/admin/settings/api-tokens', '/admin/settings/webhooks',
        ] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->has('adminPermissions'));
        }

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_support_navigation_does_not_expose_catalog_or_billing_permissions(): void
    {
        $this->actingAs(Admin::query()->create([
            'name' => 'Support preview',
            'email' => 'preview-support@example.test',
            'password' => 'test-only-password',
            'role' => AdminRole::Support,
        ]), 'admin');

        $this->get('/admin/inquiries')->assertOk();
        $this->get('/admin/support/tickets')->assertOk();
        $this->get('/admin/projects')->assertOk();
        $this->get('/admin/maintenance/requests')->assertOk();
        $this->get('/admin/maintenance/plans')->assertForbidden();
        $this->get('/admin/settings/inquiry-notifications')->assertForbidden();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/invoices')->assertForbidden();
    }

    public function test_new_public_destinations_render_with_empty_and_published_plan_states(): void
    {
        $this->get('/maintenance')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/MaintenancePlans/Index')->has('plans', 0));
        $plan = $this->maintenancePlan();
        foreach ([
            '/forgot-password' => 'Client/Auth/ForgotPassword',
            '/reset-password/test-token?email=preview%40example.test' => 'Client/Auth/ResetPassword',
            '/maintenance' => 'Client/MaintenancePlans/Index',
            '/maintenance/'.$plan->slug => 'Client/MaintenancePlans/Show',
        ] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
        $plan->update(['published' => false]);
        $this->get('/maintenance/'.$plan->slug)->assertNotFound();
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_new_customer_destinations_require_sign_in_and_email_verification(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $maintenance = $this->maintenanceRequest($user);
        $urls = [
            '/client-area/projects', '/client-area/projects/'.$project->id,
            '/client-area/maintenance', '/client-area/maintenance/'.$maintenance->id,
            '/client-area/maintenance/'.$maintenance->id.'/checkout',
        ];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->unverified()->create());
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/verify-email');
        }
    }

    public function test_owned_project_and_maintenance_details_render_without_exposing_private_notes(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $maintenance = $this->maintenanceRequest($user);
        $this->actingAs($user)->get('/client-area/projects/'.$project->id)->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Client/Projects/Show')->has('project.milestones', 0)->has('project.files', 0));
        $this->get('/client-area/maintenance/'.$maintenance->id)->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Client/Account/Maintenance/Show')->missing('request.internal_notes')->where('request.status', 'requested'));
        $this->get('/client-area/maintenance/'.$maintenance->id.'/checkout')
            ->assertRedirect('/client-area/maintenance/'.$maintenance->id)->assertSessionHasErrors('checkout');
        $this->flushSession();
        $this->actingAs(User::factory()->create());
        $this->get('/client-area/projects/'.$project->id)->assertNotFound();
        $this->get('/client-area/maintenance/'.$maintenance->id)->assertNotFound();
        $this->get('/client-area/maintenance/'.$maintenance->id.'/checkout')->assertNotFound();
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_maintenance_payment_review_uses_the_existing_checkout_without_creating_an_order(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test services', 'slug' => 'test-services', 'status' => true]);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Test maintenance', 'slug' => 'test-maintenance', 'description' => 'Fixture only.', 'status' => true]);
        $price = $product->prices()->create(['name' => 'Test monthly plan', 'billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 19, 'setup_fee' => 0, 'enabled' => true]);
        $plan = $this->maintenancePlan();
        $plan->update(['product_price_id' => $price->id]);
        $maintenance = $this->maintenanceRequest($user, $plan);
        $maintenance->update(['status' => 'awaiting_payment']);
        $this->actingAs($user)->get('/client-area/maintenance/'.$maintenance->id.'/checkout')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Client/Checkout/Create')
                ->where('cart.items.0.id', $price->id)
                ->where('checkoutUrl', route('account.maintenance.checkout.pay', $maintenance)));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_new_admin_detail_destinations_preserve_authentication_and_role_boundaries(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $maintenance = $this->maintenanceRequest($user);
        $inquiry = ProjectInquiry::query()->create(['name' => 'Test inquiry', 'email' => 'preview@example.test', 'service' => 'web-development', 'message' => 'Test inquiry requirements for route coverage.']);
        $details = [
            '/admin/inquiries/'.$inquiry->id => 'Admin/Support/Inquiries/Show',
            '/admin/projects/'.$project->id => 'Admin/Projects/Show',
            '/admin/maintenance/requests/'.$maintenance->id => 'Admin/Maintenance/Request',
        ];
        foreach ([...array_keys($details), '/admin/settings/inquiry-notifications', '/admin/maintenance/plans'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $admin = Admin::query()->create(['name' => 'Test administrator', 'email' => 'route-admin@example.test', 'password' => 'password', 'role' => AdminRole::SuperAdmin]);
        $this->actingAs($admin, 'admin');
        foreach ($details as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
        $admin->update(['role' => AdminRole::Catalog]);
        $this->actingAs($admin->fresh(), 'admin');
        foreach (array_keys($details) as $url) {
            $this->get($url)->assertForbidden();
        }
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    private function maintenancePlan(): MaintenancePlan
    {
        return MaintenancePlan::query()->create([
            'name' => 'Test WordPress care', 'slug' => 'test-wordpress-care', 'platform' => 'wordpress',
            'summary' => 'Fixture for route coverage only.',
            'scope' => str_repeat('Agreed maintenance scope with detailed responsibilities. ', 30),
            'exclusions' => 'Custom development is separately scoped.', 'published' => true,
        ]);
    }

    private function maintenanceRequest(User $user, ?MaintenancePlan $plan = null): MaintenanceRequest
    {
        $plan ??= $this->maintenancePlan();

        return MaintenanceRequest::query()->create([
            'maintenance_plan_id' => $plan->id, 'user_id' => $user->id,
            'plan_snapshot' => app(MaintenancePlanService::class)->planPayload($plan),
            'requirements' => 'Test customer maintenance request requirements.', 'status' => 'requested',
            'internal_notes' => 'Private administrative notes.', 'scope_acknowledged_at' => now(),
        ]);
    }

    private function project(User $user): Project
    {
        return Project::query()->create([
            'user_id' => $user->id, 'title' => 'Test project workspace',
            'description' => str_repeat('Agreed project scope for route coverage. ', 30), 'status' => 'active',
        ]);
    }
}
