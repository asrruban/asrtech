<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
        $this->getJson('/api/v1/orders', ['Authorization' => 'Bearer wrong_123'])->assertUnauthorized();
    }

    public function test_valid_token_grants_access_and_tracks_usage(): void
    {
        [, $plain] = ApiToken::issue($this->admin(), 'integration');

        $this->getJson('/api/v1/orders', ['Authorization' => "Bearer {$plain}"])
            ->assertOk()
            ->assertJsonStructure(['data', 'links']);

        $this->assertNotNull(ApiToken::query()->sole()->last_used_at);
    }

    public function test_revoked_tokens_are_rejected(): void
    {
        [$token, $plain] = ApiToken::issue($this->admin(), 'integration');
        $token->update(['revoked_at' => now()]);

        $this->getJson('/api/v1/orders', ['Authorization' => "Bearer {$plain}"])
            ->assertUnauthorized();
    }

    public function test_products_endpoint_lists_active_products(): void
    {
        [, $plain] = ApiToken::issue($this->admin(), 'integration');

        $category = Category::query()->create([
            'name' => 'Modules', 'slug' => 'modules', 'status' => true,
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'API Product',
            'slug' => 'api-product',
            'type' => 'whmcs_module',
            'price' => 49,
            'status' => true,
            'featured' => false,
        ]);

        $this->getJson('/api/v1/products', ['Authorization' => "Bearer {$plain}"])
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Product']);
    }

    public function test_admin_can_issue_and_revoke_tokens_from_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/api-tokens')
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/api-tokens', ['name' => 'Zapier'])
            ->assertRedirect('/admin/settings/api-tokens');

        $token = ApiToken::query()->sole();
        $this->assertSame('Zapier', $token->name);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/settings/api-tokens/{$token->id}")
            ->assertRedirect('/admin/settings/api-tokens');

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);
    }
}
