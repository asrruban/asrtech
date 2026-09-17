<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_users_by_name_and_email(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Zephyr Quill', 'email' => 'zephyr@example.com']);

        $response = $this->actingAs($admin, 'admin')->getJson('/admin/search?q=zephyr');

        $response->assertOk();
        $groups = collect($response->json('groups'));
        $userGroup = $groups->firstWhere('label', 'Users');

        $this->assertNotNull($userGroup);
        $this->assertSame('Zephyr Quill', $userGroup['items'][0]['title']);
        $this->assertStringContainsString('/admin/users/', $userGroup['items'][0]['url']);
    }

    public function test_search_respects_permissions(): void
    {
        $catalog = $this->admin(['role' => AdminRole::Catalog]);
        User::factory()->create(['name' => 'Zephyr Quill', 'email' => 'zephyr@example.com']);

        $response = $this->actingAs($catalog, 'admin')->getJson('/admin/search?q=zephyr');

        $labels = collect($response->json('groups'))->pluck('label');
        $this->assertNotContains('Users', $labels);
        $this->assertNotContains('Invoices', $labels);
    }

    public function test_short_queries_return_nothing(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->getJson('/admin/search?q=a')
            ->assertOk()
            ->assertExactJson(['groups' => []]);
    }

    private function admin(array $attributes = []): Admin
    {
        return Admin::query()->create(array_merge([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ], $attributes));
    }
}
