<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatibility_filter_has_inclusive_numeric_bounds_and_ignores_unknown_or_unpublished_claims(): void
    {
        $product = $this->product('verified');
        $this->range($product, 'whmcs', '8.9', '8.11.2');
        $unknown = $this->product('unknown');
        $unknown->update(['compatibility' => 'WHMCS 8.9 to 8.11.2']);
        $draft = $this->product('draft');
        $this->range($draft, 'whmcs', '8.9', '8.11.2', false);
        $hidden = $this->product('hidden');
        $hidden->update(['status' => false]);
        $this->range($hidden, 'whmcs', '8.9', '8.11.2');

        foreach (['8.9', '8.9.0', '8.10', '8.11.2'] as $version) {
            $this->get('/products?whmcs_version='.$version)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('filters.whmcs_version', $version)
                ->where('products.total', 1)
                ->where('products.data.0.slug', 'verified'));
        }

        foreach (['8.8.99', '8.11.3', '9.0'] as $version) {
            $this->get('/products?whmcs_version='.$version)->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        }

        $this->get('/products')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.total', 3));
    }

    public function test_multiple_platform_filters_intersect_and_disjoint_ranges_do_not_infer_support(): void
    {
        $whmcs = $this->product('whmcs');
        $this->range($whmcs, 'whmcs', '8.10', '8.10.2');
        $this->range($whmcs, 'whmcs', '8.12', '8.12.1');
        $this->range($whmcs, 'php', '8.2', '8.3.5');
        $wordpress = $this->product('wordpress', 'wordpress_plugin');
        $this->range($wordpress, 'wordpress', '6.6', '6.7.1');
        $this->range($wordpress, 'php', '8.2', '8.4.0');
        $phpOnly = $this->product('php-only');
        $this->range($phpOnly, 'php', '8.2', '8.4.0');

        $this->get('/products?whmcs_version=8.10.1&php_version=8.3')->assertInertia(fn (Assert $page) => $page->where('products.total', 1)->where('products.data.0.slug', 'whmcs'));
        $this->get('/products?whmcs_version=8.11')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/products?whmcs_version=8.10&wordpress_version=6.7')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/products?wordpress_version=6.7&php_version=8.4')->assertInertia(fn (Assert $page) => $page->where('products.total', 1)->where('products.data.0.slug', 'wordpress'));
        $this->get('/products?whmcs_version=8.10&php_version=8.4')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
    }

    public function test_exact_versions_treat_omitted_patch_as_zero_and_product_details_hide_drafts(): void
    {
        $product = $this->product('exact');
        $this->range($product, 'php', '8.2', '8.2');
        $this->range($product, 'whmcs', '8.10', '8.12', false);
        $this->get('/products?php_version=8.2.1')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get($product->storefrontUrl())->assertInertia(fn (Assert $page) => $page
            ->has('product.compatibility_ranges', 1)
            ->where('product.compatibility_ranges.0.platform', 'php')
            ->where('product.compatibility_ranges.0.minimum_version', '8.2.0')
            ->where('product.compatibility_ranges.0.maximum_version', '8.2.0'));
    }

    public function test_version_filters_preserve_search_type_category_subcategory_and_pagination(): void
    {
        $category = $this->category();
        $group = Group::query()->create(['category_id' => $category->id, 'name' => 'Gateways', 'slug' => 'gateways', 'status' => true]);
        for ($index = 0; $index < 13; $index++) {
            $product = $this->product('gateway-'.$index);
            $product->update(['group_id' => $group->id]);
            $this->range($product, 'whmcs', '8.9', '8.11');
        }
        $other = $this->product('unrelated');
        $this->range($other, 'whmcs', '8.9', '8.11');
        $url = route('subcategories.show', ['category' => $category, 'group' => $group]);
        $this->get($url.'?search=gateway&type=whmcs_module&whmcs_version=8.10')
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.total', 13)
                ->where('products.last_page', 2)
                ->where('products.next_page_url', fn (string $next): bool => str_contains($next, 'search=gateway') && str_contains($next, 'type=whmcs_module') && str_contains($next, 'whmcs_version=8.10') && str_contains($next, 'page=2')));
        $this->get($url.'?search=gateway&type=wordpress_plugin&whmcs_version=8.10')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
    }

    public function test_invalid_or_prerelease_filter_versions_return_validation_errors(): void
    {
        foreach (['8', '8.x', '8.2-beta', '8.2.0-rc1', '8.02', '8.2.1000', '8.2.1.3', '-1.2'] as $version) {
            $this->getJson('/products?php_version='.urlencode($version))->assertUnprocessable()->assertJsonValidationErrors('php_version');
        }
        $this->getJson('/products?whmcs_version[]=8.2')->assertUnprocessable()->assertJsonValidationErrors('whmcs_version');
    }

    public function test_catalog_admin_can_publish_edit_and_remove_ranges_without_modifying_legacy_compatibility(): void
    {
        $this->actingAs($this->admin(AdminRole::Catalog), 'admin');
        $data = $this->payload();
        $data['compatibility'] = 'Retained platform notes';
        $data['compatibility_ranges'] = [['platform' => 'whmcs', 'minimum_version' => '8.9', 'maximum_version' => '8.11.2', 'published' => true]];
        $this->post('/admin/products', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/products');
        $product = Product::query()->where('slug', 'new-product')->sole();
        $this->assertDatabaseHas('product_compatibilities', ['product_id' => $product->id, 'minimum_version' => '8.9.0', 'maximum_version' => '8.11.2', 'published' => true]);
        $this->get('/admin/products/'.$product->id.'/edit')->assertInertia(fn (Assert $page) => $page->has('product.compatibility_ranges', 1));

        unset($data['compatibility_ranges']);
        $this->put('/admin/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect('/admin/products');
        $this->assertDatabaseCount('product_compatibilities', 1);
        $data['compatibility_ranges'] = [['platform' => 'php', 'minimum_version' => '8.2', 'maximum_version' => '8.4', 'published' => false]];
        $this->put('/admin/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect('/admin/products');
        $this->assertDatabaseHas('product_compatibilities', ['product_id' => $product->id, 'platform' => 'php', 'published' => false]);
        $this->assertDatabaseMissing('product_compatibilities', ['product_id' => $product->id, 'platform' => 'whmcs']);
        unset($data['compatibility_ranges']);
        $data['compatibility_ranges_present'] = true;
        $this->put('/admin/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect('/admin/products');
        $this->assertDatabaseCount('product_compatibilities', 0);
        $this->assertSame('Retained platform notes', $product->fresh()->compatibility);
    }

    public function test_admin_ranges_reject_reversed_bounds_unknown_platforms_and_untrusted_extra_fields(): void
    {
        $this->actingAs($this->admin(AdminRole::Catalog), 'admin');
        $payload = $this->payload();
        foreach ([
            ['whmcs', '8.10', '8.9', 'maximum_version'],
            ['php', '8.2', '8.x', 'maximum_version'],
            ['php', '8.2-rc1', '8.3', 'minimum_version'],
            ['joomla', '1.0', '2.0', 'platform'],
        ] as [$platform, $min, $max, $error]) {
            $payload['compatibility_ranges'] = [['platform' => $platform, 'minimum_version' => $min, 'maximum_version' => $max, 'published' => true]];
            $this->post('/admin/products', $payload)->assertSessionHasErrors('compatibility_ranges.0.'.$error);
        }
        $payload['compatibility_ranges'] = [['platform' => 'php', 'minimum_version' => '8.2', 'maximum_version' => '8.3', 'published' => true, 'minimum_version_number' => 0]];
        $this->post('/admin/products', $payload)->assertSessionHasErrors('compatibility_ranges.0');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_customers_and_other_admin_roles_cannot_publish_compatibility(): void
    {
        $payload = $this->payload();
        $payload['compatibility_ranges'] = [['platform' => 'php', 'minimum_version' => '8.2', 'maximum_version' => '8.3', 'published' => true]];
        $this->actingAs(User::factory()->create())->post('/admin/products', $payload)->assertRedirect('/admin/login');
        $this->actingAs($this->admin(AdminRole::Support), 'admin')->post('/admin/products', $payload)->assertForbidden();
        $this->assertDatabaseCount('product_compatibilities', 0);
    }

    private function category(): Category
    {
        return Category::query()->firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'status' => true]);
    }

    private function product(string $slug, string $type = 'whmcs_module'): Product
    {
        return Product::query()->create(['category_id' => $this->category()->id, 'name' => $slug, 'slug' => $slug, 'type' => $type, 'price' => 10, 'status' => true]);
    }

    private function range(Product $product, string $platform, string $minimum, string $maximum, bool $published = true): ProductCompatibility
    {
        return $product->compatibilityRanges()->create(['platform' => $platform, 'minimum_version' => $minimum, 'maximum_version' => $maximum, 'published' => $published]);
    }

    private function admin(AdminRole $role): Admin
    {
        return Admin::query()->create(['name' => 'Catalog Tester', 'email' => $role->value.'@example.test', 'password' => 'password', 'role' => $role]);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'category_id' => $this->category()->id,
            'name' => 'New Product',
            'slug' => 'new-product',
            'type' => 'whmcs_module',
            'status' => true,
            'featured' => false,
            'prices' => [['billing_cycle' => 'one_time', 'currency' => 'USD', 'price' => 10, 'setup_fee' => 0, 'enabled' => true]],
            'seo' => ['robots' => 'index,follow', 'twitter_card' => 'summary'],
        ];
    }
}
