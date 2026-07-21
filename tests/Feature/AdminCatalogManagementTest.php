<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category_group_and_license_product(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/categories', [
            'name' => 'Software',
            'description' => 'Downloadable software',
            'status' => true,
        ])->assertRedirect('/admin/categories');

        $category = Category::query()->where('name', 'Software')->firstOrFail();

        $this->post('/admin/groups', [
            'category_id' => $category->id,
            'name' => 'Security',
            'description' => 'Security licenses',
            'status' => true,
        ])->assertRedirect('/admin/groups');

        $group = Group::query()->where('name', 'Security')->firstOrFail();

        $this->post('/admin/products', [
            'category_id' => $category->id,
            'group_id' => $group->id,
            'name' => 'Antivirus Pro',
            'slug' => 'antivirus-pro-suite',
            'sku' => 'AV-PRO-1Y',
            'type' => 'license',
            'short_description' => 'Professional license',
            'description' => 'One year license',
            'documentation_title' => 'Antivirus Pro Documentation',
            'documentation_content' => 'Install the package and activate the license.',
            'documentation_meta_title' => 'Antivirus Pro Documentation | ASRTech',
            'documentation_meta_description' => 'Installation and activation documentation for Antivirus Pro.',
            'documentation_keywords' => 'antivirus documentation, activation',
            'documentation_robots' => 'index,follow',
            'documentation_open_graph_image' => 'https://example.com/docs.png',
            'status' => true,
            'featured' => true,
            'prices' => $this->prices('49.99'),
            'seo' => $this->seo('Antivirus Pro'),
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Antivirus Pro',
            'slug' => 'antivirus-pro-suite',
            'category_id' => $category->id,
            'group_id' => $group->id,
            'type' => 'license',
            'price' => 49.99,
            'documentation_title' => 'Antivirus Pro Documentation',
            'documentation_meta_title' => 'Antivirus Pro Documentation | ASRTech',
            'documentation_robots' => 'index,follow',
        ]);
    }

    public function test_product_group_must_belong_to_selected_category(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $software = Category::query()->create([
            'name' => 'Software',
            'slug' => 'software',
            'status' => true,
        ]);
        $services = Category::query()->create([
            'name' => 'Services',
            'slug' => 'services',
            'status' => true,
        ]);
        $group = Group::query()->create([
            'category_id' => $services->id,
            'name' => 'Consulting',
            'slug' => 'consulting',
            'status' => true,
        ]);

        $this->post('/admin/products', [
            'category_id' => $software->id,
            'group_id' => $group->id,
            'name' => 'Invalid product',
            'type' => 'license',
            'status' => true,
            'featured' => false,
            'prices' => $this->prices('10.00'),
            'seo' => $this->seo('Invalid product'),
        ])->assertSessionHasErrors('group_id');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_update_and_delete_a_product(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $category = Category::query()->create([
            'name' => 'Software',
            'slug' => 'software',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Starter License',
            'slug' => 'starter-license',
            'type' => 'license',
            'price' => 20,
            'status' => true,
        ]);

        $this->put("/admin/products/{$product->id}", [
            'category_id' => $category->id,
            'group_id' => null,
            'name' => 'Professional License',
            'slug' => 'professional-license-custom',
            'sku' => 'PRO-LIC',
            'type' => 'license',
            'description' => null,
            'status' => true,
            'featured' => false,
            'prices' => $this->prices('35.00'),
            'seo' => $this->seo('Professional License'),
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Professional License',
            'sku' => 'PRO-LIC',
            'slug' => 'professional-license-custom',
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'billing_cycle' => 'one_time',
            'price' => 35,
        ]);

        $this->delete("/admin/products/{$product->id}")
            ->assertRedirect('/admin/products');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_category_with_catalog_items_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $category = Category::query()->create([
            'name' => 'Software',
            'slug' => 'software',
            'status' => true,
        ]);
        Group::query()->create([
            'category_id' => $category->id,
            'name' => 'Security',
            'slug' => 'security',
            'status' => true,
        ]);

        $this->delete("/admin/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_can_manage_product_types_but_cannot_delete_one_in_use(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/product-types', [
            'name' => 'Server Tools',
            'slug' => 'server-tools',
            'description' => 'Tools for server operators.',
            'status' => true,
        ])->assertRedirect('/admin/product-types');

        $productType = ProductType::query()->where('slug', 'server-tools')->firstOrFail();
        $this->assertSame('server_tools', $productType->key);

        $this->put("/admin/product-types/{$productType->id}", [
            'name' => 'Hosting Tools',
            'slug' => 'hosting-tools',
            'description' => 'Hosting automation tools.',
            'status' => true,
        ])->assertRedirect('/admin/product-types');

        $productType->refresh();
        $this->assertSame('hosting-tools', $productType->slug);
        $this->assertSame('server_tools', $productType->key);

        $unusedType = ProductType::query()->create([
            'name' => 'Temporary Type',
            'key' => 'temporary_type',
            'slug' => 'temporary-type',
            'status' => true,
        ]);
        $this->delete("/admin/product-types/{$unusedType->id}")
            ->assertRedirect('/admin/product-types');
        $this->assertDatabaseMissing('product_types', ['id' => $unusedType->id]);

        $category = Category::query()->create([
            'name' => 'Hosting',
            'slug' => 'hosting',
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Server Monitor',
            'slug' => 'server-monitor',
            'type' => $productType->key,
            'price' => 15,
            'status' => true,
        ]);

        $this->delete("/admin/product-types/{$productType->id}")
            ->assertSessionHasErrors('product_type');
    }

    public function test_admin_can_upload_a_featured_product_image(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin(), 'admin');
        $category = Category::query()->create([
            'name' => 'Software',
            'slug' => 'software',
            'status' => true,
        ]);

        $this->post('/admin/products', [
            'category_id' => $category->id,
            'group_id' => null,
            'name' => 'Image Product',
            'slug' => 'custom-image-product',
            'type' => 'license',
            'featured_image_upload' => UploadedFile::fake()->image('featured.jpg', 1200, 675),
            'status' => true,
            'featured' => false,
            'prices' => $this->prices('25.00'),
            'seo' => $this->seo('Image Product'),
        ])->assertRedirect('/admin/products');

        $product = Product::query()->where('slug', 'custom-image-product')->firstOrFail();
        $originalImage = (string) $product->featured_image;
        $this->assertStringStartsWith('/storage/products/', $originalImage);
        Storage::disk('uploads')->assertExists(str_replace('/storage/', '', $originalImage));

        $this->post("/admin/products/{$product->id}", [
            '_method' => 'put',
            'category_id' => $category->id,
            'group_id' => null,
            'name' => 'Image Product',
            'slug' => 'custom-image-product',
            'type' => 'license',
            'featured_image' => $originalImage,
            'featured_image_upload' => UploadedFile::fake()->image('replacement.png', 1200, 675),
            'status' => true,
            'featured' => false,
            'prices' => $this->prices('25.00'),
            'seo' => $this->seo('Image Product'),
        ])->assertRedirect('/admin/products');

        $replacementImage = (string) $product->refresh()->featured_image;
        $this->assertNotSame($originalImage, $replacementImage);
        Storage::disk('uploads')->assertExists(str_replace('/storage/', '', $replacementImage));
    }

    public function test_catalog_management_requires_an_admin_session(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
        $this->get('/admin/categories')->assertRedirect('/admin/login');
        $this->get('/admin/groups')->assertRedirect('/admin/login');
        $this->get('/admin/product-types')->assertRedirect('/admin/login');
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Catalog Admin',
            'email' => 'catalog@example.com',
            'password' => 'a-secure-password',
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function prices(string $amount): array
    {
        return [[
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => $amount,
            'sale_price' => null,
            'setup_fee' => '0.00',
            'enabled' => true,
        ]];
    }

    /** @return array<string, mixed> */
    private function seo(string $title): array
    {
        return [
            'meta_title' => $title,
            'meta_description' => 'Search-friendly description.',
            'keywords' => 'license, ASRTech',
            'canonical_url' => null,
            'robots' => 'index,follow',
            'open_graph_title' => null,
            'open_graph_description' => null,
            'open_graph_image' => null,
            'twitter_card' => 'summary_large_image',
            'schema_json' => null,
        ];
    }
}
