<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_client_authentication_does_not_grant_admin_access(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_is_rendered(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Auth/Login'));
    }

    public function test_admin_can_sign_in_with_local_credentials(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            'password' => 'a-secure-password',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_invalid_admin_credentials_are_rejected(): void
    {
        Admin::query()->create([
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            'password' => 'a-secure-password',
        ]);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'incorrect-password',
        ])->assertRedirect('/admin/login')->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_can_visit_dashboard_and_sign_out(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            'password' => 'a-secure-password',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.admins', 1));

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }
}
