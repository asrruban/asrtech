<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_requires_permission(): void
    {
        $support = $this->admin(['role' => AdminRole::Support]);

        $this->actingAs($support, 'admin')
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_reports_page_loads_for_billing_admin(): void
    {
        $billing = $this->admin(['role' => AdminRole::Billing]);

        $this->actingAs($billing, 'admin')
            ->get('/admin/reports')
            ->assertOk();
    }

    public function test_orders_csv_export_streams_rows(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/reports/export/orders');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('order_number', $response->streamedContent());
    }

    public function test_invalid_export_dataset_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/export/secrets')
            ->assertNotFound();
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
