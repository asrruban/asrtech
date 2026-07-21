<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
        ]);
    }

    public function test_unconfigured_providers_are_rejected(): void
    {
        config(['services.github.client_id' => null]);

        $this->get('/auth/github/redirect')->assertNotFound();
        $this->get('/auth/unknown/redirect')->assertNotFound();
    }

    public function test_google_callback_creates_a_verified_user_and_logs_in(): void
    {
        $this->mockSocialiteUser(id: 'google-123', name: 'Al Amin', email: 'alamin@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/client-area');

        $user = User::query()->where('email', 'alamin@example.com')->sole();
        $this->assertSame('google', $user->social_provider);
        $this->assertSame('google-123', $user->social_provider_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_links_an_existing_password_account(): void
    {
        $existing = User::factory()->create(['email' => 'alamin@example.com']);

        $this->mockSocialiteUser(id: 'google-999', name: 'Al Amin', email: 'alamin@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/client-area');

        $this->assertSame(1, User::query()->count());
        $this->assertSame('google-999', $existing->fresh()->social_provider_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_returning_social_users_are_matched_by_provider_id(): void
    {
        $user = User::factory()->create([
            'email' => 'old-email@example.com',
            'social_provider' => 'google',
            'social_provider_id' => 'google-123',
        ]);

        $this->mockSocialiteUser(id: 'google-123', name: 'Al Amin', email: 'new-email@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/client-area');

        $this->assertSame(1, User::query()->count());
        $this->assertAuthenticatedAs($user);
    }

    private function mockSocialiteUser(string $id, string $name, string $email): void
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
