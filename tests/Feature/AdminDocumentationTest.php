<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_license_integration_docs_require_an_admin_session(): void
    {
        $this->get('/admin/docs')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_the_license_integration_docs(): void
    {
        $this->actingAs(Admin::query()->create([
            'name' => 'Documentation Admin',
            'email' => 'docs@example.com',
            'password' => 'a-secure-password',
        ]), 'admin');

        $this->get('/admin/docs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Docs/Index')
                ->where('licenseApi.endpoint', route('license.verify'))
                ->where('licenseApi.method', 'POST')
                ->where('licenseApi.rate_limit', '60 requests per minute')
                ->has('licenseApi.fields', 4)
                ->where('licenseApi.fields.0.name', 'license_key')
                ->where('licenseApi.fields.0.required', true)
                ->has('licenseApi.statuses', 5)
                ->where('licenseApi.statuses.0.value', 'active'));
    }
}
