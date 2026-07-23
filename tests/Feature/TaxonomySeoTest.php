<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaxonomySeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_categories_and_subcategories_with_seo(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/categories', [
            'name' => 'WHMCS Modules',
            'description' => 'Automation modules for hosting businesses.',
            'status' => true,
            'seo' => $this->seo('WHMCS Modules | ASRTech'),
        ])->assertRedirect('/admin/categories');

        $category = Category::query()->where('slug', 'whmcs-modules')->firstOrFail();

        $this->assertDatabaseHas('seo_metadata', [
            'seoable_type' => Category::class,
            'seoable_id' => $category->id,
            'meta_title' => 'WHMCS Modules | ASRTech',
            'canonical_url' => route('categories.show', $category),
        ]);

        $this->post('/admin/subcategories', [
            'category_id' => $category->id,
            'name' => 'Server Automation',
            'description' => 'Automate server provisioning and management.',
            'status' => true,
            'seo' => $this->seo('Server Automation Modules | ASRTech'),
        ])->assertRedirect('/admin/subcategories');

        $subcategory = Group::query()->where('slug', 'server-automation')->firstOrFail();

        $this->assertDatabaseHas('seo_metadata', [
            'seoable_type' => Group::class,
            'seoable_id' => $subcategory->id,
            'meta_title' => 'Server Automation Modules | ASRTech',
            'canonical_url' => route('subcategories.show', [
                'category' => $category,
                'group' => $subcategory,
            ]),
        ]);
    }

    public function test_public_category_and_subcategory_pages_are_scoped_and_seo_ready(): void
    {
        $this->withoutVite();

        $category = Category::query()->create([
            'name' => 'WHMCS Modules',
            'slug' => 'whmcs-modules',
            'description' => 'Automation modules for hosting businesses.',
            'status' => true,
        ]);
        $subcategory = Group::query()->create([
            'category_id' => $category->id,
            'name' => 'Server Automation',
            'slug' => 'server-automation',
            'description' => 'Automate server provisioning and management.',
            'status' => true,
        ]);
        $category->seo()->create([
            'meta_title' => 'WHMCS Modules | ASRTech',
            'meta_description' => 'Browse professional WHMCS modules.',
            'robots' => 'index,follow',
            'twitter_card' => 'summary_large_image',
        ]);
        $subcategory->seo()->create([
            'meta_title' => 'Server Automation Modules | ASRTech',
            'meta_description' => 'Browse server automation modules.',
            'robots' => 'index,follow',
            'twitter_card' => 'summary_large_image',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'group_id' => $subcategory->id,
            'name' => 'Provisioning Toolkit',
            'slug' => 'provisioning-toolkit',
            'type' => 'whmcs_module',
            'price' => 49,
            'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'General Module',
            'slug' => 'general-module',
            'type' => 'whmcs_module',
            'price' => 29,
            'status' => true,
        ]);

        $this->get('/categories/whmcs-modules')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Index')
                ->where('landing.kind', 'category')
                ->where('landing.seo.meta_title', 'WHMCS Modules | ASRTech')
                ->has('landing.subcategories', 1)
                ->has('products.data', 2));

        $this->get('/categories/whmcs-modules/server-automation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Index')
                ->where('landing.kind', 'subcategory')
                ->where('landing.parent.name', 'WHMCS Modules')
                ->where('landing.seo.meta_title', 'Server Automation Modules | ASRTech')
                ->has('products.data', 1)
                ->where('products.data.0.slug', 'provisioning-toolkit'));
    }

    public function test_admin_can_generate_structured_taxonomy_seo_with_openai(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'gpt-5.6',
        ]);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'meta_title' => 'WHMCS Modules for Hosting Automation | ASRTech',
                            'meta_description' => 'Discover WHMCS modules that streamline provisioning, billing, and daily hosting operations for growing service providers.',
                            'keywords' => 'WHMCS modules, hosting automation, provisioning',
                            'open_graph_title' => 'WHMCS Hosting Automation Modules',
                            'open_graph_description' => 'Explore practical WHMCS modules built for hosting automation.',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ]],
            ]),
        ]);
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/admin/seo/generate', [
            'type' => 'category',
            'name' => 'WHMCS Modules',
            'description' => 'Automation modules for hosting businesses.',
            'parent_name' => null,
            'canonical_url' => 'https://asrtech.test/categories/whmcs-modules',
        ])
            ->assertOk()
            ->assertJsonPath('seo.meta_title', 'WHMCS Modules for Hosting Automation | ASRTech')
            ->assertJsonPath('seo.schema_json.@type', 'CollectionPage')
            ->assertJsonPath('seo.schema_json.url', 'https://asrtech.test/categories/whmcs-modules');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['model'] === 'gpt-5.6'
            && $request['store'] === false
            && $request['text']['format']['type'] === 'json_schema');
    }

    public function test_ai_seo_generation_reports_missing_configuration(): void
    {
        config(['services.openai.api_key' => null]);
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/admin/seo/generate', [
            'type' => 'category',
            'name' => 'WHMCS Modules',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ai');
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'SEO Admin',
            'email' => 'seo-catalog@example.com',
            'password' => 'a-secure-password',
        ]);
    }

    /** @return array<string, mixed> */
    private function seo(string $title): array
    {
        return [
            'meta_title' => $title,
            'meta_description' => 'Search-friendly taxonomy description.',
            'keywords' => 'WHMCS, automation',
            'canonical_url' => null,
            'robots' => 'index,follow',
            'open_graph_title' => $title,
            'open_graph_description' => 'Social-friendly taxonomy description.',
            'open_graph_image' => null,
            'twitter_card' => 'summary_large_image',
            'schema_json' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
