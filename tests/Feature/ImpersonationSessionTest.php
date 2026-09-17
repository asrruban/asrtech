<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ImpersonationSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_impersonation_switches_customer_session_fingerprints_without_losing_the_admin_session(): void
    {
        $admin = Admin::query()->create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'AdminPassword']);
        $first = User::factory()->create(['password' => 'FirstPassword']);
        $second = User::factory()->create(['password' => 'SecondPassword', 'remember_token' => 'keep-this-remember-token']);
        $this->actingAs($first, 'web')->get('/client-area/projects')->assertOk();
        $this->assertNotNull(session('password_hash_web'));
        $this->actingAs($admin, 'admin')->post("/admin/users/{$second->id}/impersonate")->assertRedirect('/client-area');
        $this->assertNotNull(session('password_hash_web'));
        // A new HTTP request begins with the configured web default guard.
        Auth::shouldUse('web');
        $this->get('/client-area/projects')->assertOk();
        $this->assertAuthenticatedAs($second, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull(session('password_hash_web'));
        $this->post('/impersonation/leave')->assertRedirect("/admin/users/{$second->id}");
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNull(session('password_hash_web'));
        $this->assertSame('keep-this-remember-token', $second->fresh()->remember_token);
        $this->post("/admin/users/{$first->id}/impersonate")->assertRedirect('/client-area');
        Auth::shouldUse('web');
        $this->get('/client-area/projects')->assertOk();
        $this->assertAuthenticatedAs($first, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }
}
