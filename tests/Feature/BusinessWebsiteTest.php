<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProjectInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_pages_and_all_service_details_are_available(): void
    {
        foreach (['services' => 'Client/Services/Index', 'about' => 'Client/About', 'contact' => 'Client/Contact'] as $path => $component) {
            $this->get('/'.$path)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('business.name', 'ASR Tech')
                ->where('business.owner', 'Al Amin')
                ->where('seo.canonical_url', 'https://www.asrtech.bd/'.$path));
        }

        foreach (array_keys(config('asrtech.services')) as $slug) {
            $this->get('/services/'.$slug)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Client/Services/Show')
                ->where('serviceSlug', $slug));
        }

        $this->get('/services/nonexistent')->assertNotFound();
        $this->get('/contact?service=server-management')->assertInertia(fn (Assert $page) => $page->where('selectedService', 'server-management'));
        $this->get('/contact?service=invalid')->assertInertia(fn (Assert $page) => $page->where('selectedService', ''));
    }

    public function test_public_metadata_is_server_rendered_and_private_pages_are_not_indexable(): void
    {
        $response = $this->get('/services')->assertOk();
        $response->assertSee('<title data-inertia="">Services | ASR Tech</title>', false)
            ->assertSee('property="og:url" content="https://www.asrtech.bd/services"', false)
            ->assertSee('type="application/ld+json"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
        $this->get('/login')->assertOk()->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_initial_product_social_image_uses_featured_image_and_preserves_absolute_cdn_urls(): void
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Image Test Module',
            'slug' => 'image-test-module',
            'type' => 'whmcs_module',
            'status' => true,
            'featured_image' => '/storage/products/module-preview.webp',
        ]);
        $this->get('/products/whmcs/image-test-module')->assertOk()
            ->assertSee('property="og:image" content="https://www.asrtech.bd/storage/products/module-preview.webp"', false)
            ->assertSee('name="twitter:image" content="https://www.asrtech.bd/storage/products/module-preview.webp"', false);

        $cdnImage = 'https://cdn.example.test/module.webp?signature=abc123';
        $product->seo()->create(['open_graph_image' => $cdnImage]);
        $this->get('/products/whmcs/image-test-module')->assertOk()
            ->assertSee('property="og:image" content="'.$cdnImage.'"', false);

        $product->seo()->update(['open_graph_image' => 'images/product-social.webp']);
        $this->get('/products/whmcs/image-test-module')->assertOk()
            ->assertSee('property="og:image" content="https://www.asrtech.bd/images/product-social.webp"', false);
    }

    public function test_impersonation_banner_requires_a_matching_session_and_both_authenticated_guards(): void
    {
        $this->get('/about')->assertInertia(fn (Assert $page) => $page->where('auth.impersonating', false));
        $admin = Admin::query()->create(['name' => 'Administrator', 'email' => 'admin@example.test', 'password' => 'password']);
        $customer = User::factory()->create();
        $this->actingAs($admin, 'admin')->actingAs($customer, 'web')
            ->get('/about')->assertInertia(fn (Assert $page) => $page
            ->where('auth.admin.id', $admin->id)
            ->where('auth.user.id', $customer->id)
            ->where('auth.impersonating', false));

        $this->withSession(['impersonating_user_id' => $customer->id])
            ->get('/about')->assertInertia(fn (Assert $page) => $page->where('auth.impersonating', true));
        $this->withSession(['impersonating_user_id' => $customer->id + 1])
            ->get('/about')->assertInertia(fn (Assert $page) => $page->where('auth.impersonating', false));

        auth('admin')->logout();
        $this->withSession(['impersonating_user_id' => $customer->id])
            ->get('/about')->assertInertia(fn (Assert $page) => $page->where('auth.impersonating', false));
    }

    public function test_guest_inquiry_is_persisted_without_creating_a_customer_or_sending_mail(): void
    {
        Mail::fake();
        $this->post('/contact', [...$this->inquiry(), 'status' => 'closed'])->assertRedirect('/contact')->assertSessionHas('inquiry_received', true);
        $this->assertDatabaseHas('project_inquiries', [...$this->inquiry(), 'status' => 'new']);
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
        $this->get('/contact')->assertInertia(fn (Assert $page) => $page->where('inquiryReceived', true));
        $this->get('/contact')->assertInertia(fn (Assert $page) => $page->where('inquiryReceived', false));
    }

    public function test_invalid_inquiries_are_rejected(): void
    {
        $this->post('/contact', [
            'name' => '', 'email' => 'invalid', 'service' => 'made-up', 'message' => 'short',
        ])->assertSessionHasErrors(['name', 'email', 'service', 'message']);
        $this->assertDatabaseCount('project_inquiries', 0);
    }

    public function test_storage_failure_returns_an_error_instead_of_success(): void
    {
        ProjectInquiry::creating(fn () => throw new \RuntimeException('Storage unavailable'));
        try {
            $this->from('/contact')->post('/contact', $this->inquiry())
                ->assertRedirect('/contact')
                ->assertSessionHasErrors('submission')
                ->assertSessionMissing('inquiry_received');
        } finally {
            ProjectInquiry::flushEventListeners();
        }
        $this->assertDatabaseCount('project_inquiries', 0);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->inquiry())->assertRedirect();
        }
        $this->post('/contact', $this->inquiry())->assertTooManyRequests();
        $this->assertDatabaseCount('project_inquiries', 5);
    }

    public function test_only_admins_with_support_permission_can_review_inquiries(): void
    {
        $inquiry = ProjectInquiry::query()->create($this->inquiry());
        $this->get('/admin/inquiries')->assertRedirect('/admin/login');
        $catalog = Admin::query()->create(['name' => 'Catalog', 'email' => 'catalog@example.test', 'password' => 'password', 'role' => AdminRole::Catalog]);
        $this->actingAs($catalog, 'admin')->get('/admin/inquiries')->assertForbidden();
        $this->patch('/admin/inquiries/'.$inquiry->id, ['status' => 'closed'])->assertForbidden();
        $support = Admin::query()->create(['name' => 'Support', 'email' => 'support@example.test', 'password' => 'password', 'role' => AdminRole::Support]);
        $this->actingAs($support, 'admin')->get('/admin/inquiries')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Support/Inquiries/Index')->has('inquiries.data', 1));
        $this->from('/admin/inquiries')->patch('/admin/inquiries/'.$inquiry->id, ['status' => 'reviewed'])->assertRedirect('/admin/inquiries');
        $this->assertDatabaseHas('project_inquiries', ['id' => $inquiry->id, 'status' => 'reviewed']);
        $this->patch('/admin/inquiries/'.$inquiry->id, ['status' => 'invalid'])->assertSessionHasErrors('status');
    }

    /** @return array<string, string> */
    private function inquiry(): array
    {
        return ['name' => 'Test Visitor', 'email' => 'visitor@example.test', 'service' => 'web-development', 'message' => 'I would like to discuss a website for my business.'];
    }
}
