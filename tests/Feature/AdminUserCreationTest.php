<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_verified_user(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/users', [
            'name' => 'New Client',
            'email' => 'newclient@example.com',
            'password' => 'client-password',
            'verified' => true,
        ])->assertRedirect();

        $user = User::query()->where('email', 'newclient@example.com')->sole();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotSame('client-password', $user->password);
    }

    public function test_admin_can_create_an_unverified_user(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/users', [
            'name' => 'Pending Client',
            'email' => 'pending@example.com',
            'password' => 'client-password',
            'verified' => false,
        ]);

        $this->assertNull(User::query()->where('email', 'pending@example.com')->sole()->email_verified_at);
    }

    public function test_duplicate_emails_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $existing = User::factory()->create();

        $this->post('/admin/users', [
            'name' => 'Duplicate',
            'email' => $existing->email,
            'password' => 'client-password',
            'verified' => true,
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_login_as_a_client_and_return(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $this->post("/admin/users/{$user->id}/impersonate")
            ->assertRedirect('/client-area');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post('/impersonation/leave')
            ->assertRedirect("/admin/users/{$user->id}");

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_guests_and_clients_cannot_impersonate(): void
    {
        $user = User::factory()->create();

        $this->post("/admin/users/{$user->id}/impersonate")
            ->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create())
            ->post("/admin/users/{$user->id}/impersonate")
            ->assertRedirect('/admin/login');
    }

    public function test_leaving_without_an_admin_session_lands_on_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/impersonation/leave')
            ->assertRedirect('/');

        $this->assertGuest('web');
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Support Admin',
            'email' => 'support-admin@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
