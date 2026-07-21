<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_rendered_for_guests(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_users_can_register_and_are_sent_to_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'Al Amin',
            'email' => 'alamin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertRedirect('/verify-email');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'alamin@example.com')->sole();
        $this->assertSame('Al Amin', $user->name);
        $this->assertNotSame('secret-password', $user->password);
        $this->assertNull($user->email_verified_at);
    }

    public function test_users_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/client-area');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_login_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_duplicate_emails_cannot_register(): void
    {
        $user = User::factory()->create();

        $this->post('/register', [
            'name' => 'Duplicate',
            'email' => $user->email,
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
