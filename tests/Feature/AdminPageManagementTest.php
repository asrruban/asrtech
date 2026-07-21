<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_seo_ready_public_page(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/pages', [
            'title' => 'Privacy Policy',
            'slug' => '',
            'excerpt' => 'How ASRTech protects customer information.',
            'content' => 'Privacy policy content.',
            'template' => 'legal',
            'status' => true,
            'sort_order' => 20,
            'seo' => $this->seo(),
        ])->assertRedirect('/admin/pages');

        $page = Page::query()->where('slug', 'privacy-policy')->firstOrFail();
        $this->assertDatabaseHas('seo_metadata', [
            'seoable_type' => Page::class,
            'seoable_id' => $page->id,
            'meta_title' => 'ASRTech Privacy Policy',
        ]);

        $this->get('/pages/privacy-policy')->assertOk();
    }

    public function test_draft_pages_are_not_public(): void
    {
        Page::query()->create([
            'title' => 'Draft',
            'slug' => 'draft',
            'template' => 'default',
            'status' => false,
            'sort_order' => 0,
        ]);

        $this->get('/pages/draft')->assertNotFound();
    }

    public function test_page_management_requires_an_admin_session(): void
    {
        $this->get('/admin/pages')->assertRedirect('/admin/login');
    }

    /** @return array<string, mixed> */
    private function seo(): array
    {
        return [
            'meta_title' => 'ASRTech Privacy Policy',
            'meta_description' => 'Read the ASRTech privacy policy.',
            'keywords' => 'privacy, ASRTech',
            'canonical_url' => null,
            'robots' => 'index,follow',
            'open_graph_title' => null,
            'open_graph_description' => null,
            'open_graph_image' => null,
            'twitter_card' => 'summary_large_image',
            'schema_json' => '{"@context":"https://schema.org","@type":"WebPage"}',
        ];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Content Admin',
            'email' => 'content@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
