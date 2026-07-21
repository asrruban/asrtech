<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_products_and_their_pricing_are_public(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'WHMCS Provisioning Module',
            'slug' => 'whmcs-provisioning-module',
            'type' => 'whmcs_module',
            'short_description' => 'Automated provisioning for WHMCS.',
            'price' => 49,
            'status' => true,
            'featured' => true,
        ]);
        $product->prices()->create([
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'price' => 49,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        $this->get('/products?type=whmcs_module')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'WHMCS Provisioning Module'));

        $this->get('/products/whmcs/whmcs-provisioning-module')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show')
                ->where('product.prices.0.billing_cycle', 'monthly'));
    }

    public function test_legacy_product_urls_redirect_to_the_canonical_type_path(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Backup Manager',
            'slug' => 'backup-manager',
            'type' => 'whmcs_module',
            'price' => 99,
            'status' => true,
        ]);

        $this->get('/products/backup-manager')
            ->assertRedirect('/products/whmcs/backup-manager')
            ->assertStatus(301);

        $this->get('/products/templates/backup-manager')->assertNotFound();
    }

    public function test_product_detail_hides_internal_fields_and_orders_prices(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Backup Manager',
            'slug' => 'backup-manager',
            'sku' => 'INTERNAL-SKU-1',
            'type' => 'whmcs_module',
            'price' => 99,
            'status' => true,
            'featured' => false,
        ]);
        $product->prices()->createMany([
            ['billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 19, 'setup_fee' => 0, 'enabled' => true, 'featured' => false],
            ['billing_cycle' => 'yearly', 'currency' => 'USD', 'price' => 149, 'setup_fee' => 0, 'enabled' => true, 'featured' => true],
            ['billing_cycle' => 'one_time', 'currency' => 'USD', 'price' => 499, 'setup_fee' => 0, 'enabled' => false, 'featured' => false],
        ]);

        $this->get('/products/whmcs/backup-manager')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show')
                ->missing('product.sku')
                ->missing('product.status')
                ->missing('product.category_id')
                ->has('product.prices', 2)
                ->where('product.prices.0.billing_cycle', 'yearly')
                ->where('product.prices.0.featured', true));
    }

    public function test_related_products_from_same_category_are_listed(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        $other = Category::query()->create([
            'name' => 'Templates',
            'slug' => 'templates',
            'status' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Backup Manager',
            'slug' => 'backup-manager',
            'type' => 'whmcs_module',
            'price' => 99,
            'status' => true,
            'featured' => false,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'DNS Manager',
            'slug' => 'dns-manager',
            'type' => 'whmcs_module',
            'price' => 49,
            'status' => true,
            'featured' => false,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Hidden Module',
            'slug' => 'hidden-module',
            'type' => 'whmcs_module',
            'price' => 29,
            'status' => false,
            'featured' => false,
        ]);
        Product::query()->create([
            'category_id' => $other->id,
            'name' => 'Landing Template',
            'slug' => 'landing-template',
            'type' => 'template',
            'price' => 59,
            'status' => true,
            'featured' => false,
        ]);

        $this->get("/products/whmcs/{$product->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show')
                ->has('relatedProducts', 1)
                ->where('relatedProducts.0.slug', 'dns-manager'));
    }

    public function test_product_documentation_has_its_own_page_and_seo_metadata(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Backup Manager',
            'slug' => 'backup-manager',
            'type' => 'whmcs_module',
            'price' => 99,
            'status' => true,
            'featured' => false,
            'documentation_title' => 'Backup Manager Documentation',
            'documentation_content' => 'Install the module and enter your license key.',
            'documentation_meta_title' => 'Backup Manager Docs | ASRTech',
            'documentation_meta_description' => 'Installation and usage documentation for Backup Manager.',
            'documentation_keywords' => 'backup manager docs, WHMCS backup',
            'documentation_robots' => 'index,follow',
        ]);

        $this->get('/products/whmcs/backup-manager/documentation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Documentation')
                ->where('product.title', 'Backup Manager Documentation')
                ->where('product.seo.meta_title', 'Backup Manager Docs | ASRTech')
                ->where('product.seo.meta_description', 'Installation and usage documentation for Backup Manager.')
                ->where('product.seo.keywords', 'backup manager docs, WHMCS backup')
                ->where('product.seo.robots', 'index,follow')
                ->where('product.seo.canonical_url', route('products.documentation', [
                    'productType' => 'whmcs',
                    'product' => 'backup-manager',
                ])));
    }

    public function test_product_without_documentation_has_no_documentation_page(): void
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Empty Docs',
            'slug' => 'empty-docs',
            'type' => 'whmcs_module',
            'price' => 10,
            'status' => true,
            'featured' => false,
        ]);

        $this->get('/products/whmcs/empty-docs/documentation')->assertNotFound();
    }

    public function test_inactive_products_are_not_public(): void
    {
        $category = Category::query()->create([
            'name' => 'Private',
            'slug' => 'private',
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Unreleased Module',
            'slug' => 'unreleased-module',
            'type' => 'whmcs_module',
            'price' => 0,
            'status' => false,
            'featured' => false,
        ]);

        $this->get('/products/whmcs/unreleased-module')->assertNotFound();
    }
}
