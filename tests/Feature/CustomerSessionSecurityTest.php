<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordRecoveryLink;
use App\Models\Admin;
use App\Models\User;
use App\Services\CustomerSessionRevoker;
use App\Services\TwoFactorService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class CustomerSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    public function test_password_login_stamps_credentials_before_the_first_protected_request(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/client-area')
            ->assertSessionHas('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->password));

        // Simulate another browser resetting the password before this browser
        // has ever followed its first post-login client-area redirect.
        $user->update(['password' => 'Changed!Pass123']);
        Auth::forgetGuards();
        $this->get('/client-area/projects')->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_completed_two_factor_login_stamps_credentials_immediately(): void
    {
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->post('/two-factor-challenge', ['code' => $twoFactor->code($secret)])
            ->assertRedirect('/client-area')
            ->assertSessionHas('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->password));
    }

    public function test_social_login_stamps_even_an_initially_null_password(): void
    {
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
        $social = new SocialiteUser;
        $social->map(['id' => 'test-id', 'name' => 'Test customer', 'email' => 'social@example.test']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($social);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $this->get('/auth/google/callback')->assertRedirect('/client-area')
            ->assertSessionHas('password_hash_web', Auth::guard('web')->hashPasswordForCookie(''));
        $user = User::query()->where('email', 'social@example.test')->sole();
        $user->update(['password' => 'First!Password123']);
        Auth::forgetGuards();
        $this->get('/client-area/projects')->assertRedirect('/login');
    }

    public function test_database_revocation_preserves_colliding_admin_ids_and_other_customers(): void
    {
        config(['session.driver' => 'database', 'session.serialization' => 'php']);
        $user = User::factory()->create();
        $admin = Admin::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password']);
        $this->assertSame($user->id, $admin->id);
        $webKey = Auth::guard('web')->getName();
        $adminKey = Auth::guard('admin')->getName();
        $this->insertSession('customer', $user->id, [$webKey => $user->id]);
        $this->insertSession('admin', $admin->id, [$adminKey => $admin->id]);
        $this->insertSession('both', $user->id, [$webKey => $user->id, $adminKey => $admin->id, 'password_hash_web' => 'old', 'password_hash_admin' => 'keep', 'impersonating_user_id' => $user->id]);
        $this->insertSession('other-customer', $user->id + 1, [$webKey => $user->id + 1]);
        app(CustomerSessionRevoker::class)->revoke($user);
        $this->assertDatabaseMissing('sessions', ['id' => 'customer']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-customer']);
        $remaining = unserialize(base64_decode(DB::table('sessions')->where('id', 'both')->value('payload')), ['allowed_classes' => false]);
        $this->assertSame($admin->id, $remaining[$adminKey]);
        $this->assertSame('keep', $remaining['password_hash_admin']);
        $this->assertArrayNotHasKey($webKey, $remaining);
        $this->assertArrayNotHasKey('password_hash_web', $remaining);
        $this->assertArrayNotHasKey('impersonating_user_id', $remaining);
    }

    public function test_encrypted_json_session_revocation_keeps_admin_authentication_intact(): void
    {
        config(['session.driver' => 'database', 'session.encrypt' => true, 'session.serialization' => 'json']);
        $user = User::factory()->create();
        $webKey = Auth::guard('web')->getName();
        $adminKey = Auth::guard('admin')->getName();
        $this->insertSession('encrypted-both', $user->id, [$webKey => $user->id, $adminKey => 77]);
        app(CustomerSessionRevoker::class)->revoke($user);
        $remaining = json_decode(Crypt::decrypt(base64_decode(DB::table('sessions')->where('id', 'encrypted-both')->value('payload'))), true);
        $this->assertSame(77, $remaining[$adminKey]);
        $this->assertArrayNotHasKey($webKey, $remaining);
        $this->assertDatabaseHas('sessions', ['id' => 'encrypted-both', 'user_id' => 77]);
    }

    public function test_customer_sessions_are_not_skipped_when_deleting_multiple_chunks(): void
    {
        config(['session.driver' => 'database', 'session.serialization' => 'php']);
        $user = User::factory()->create();
        for ($i = 0; $i < 105; $i++) {
            $this->insertSession('customer-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), $user->id, [Auth::guard('web')->getName() => $user->id]);
        }
        app(CustomerSessionRevoker::class)->revoke($user);
        $this->assertDatabaseCount('sessions', 0);
    }

    public function test_recovery_preserves_existing_mixed_case_account_email(): void
    {
        $user = User::factory()->create(['email' => 'MixedCase@example.test']);
        Queue::fake();
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect('/forgot-password');
        Queue::assertPushed(SendPasswordRecoveryLink::class, fn ($job) => $job->email === $user->email);
        (new SendPasswordRecoveryLink($user->email))->handle();
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::createToken($user);
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'New!Password123', 'password_confirmation' => 'New!Password123'])
            ->assertRedirect('/login')->assertSessionHasNoErrors();
    }

    private function insertSession(string $id, int $userId, array $data): void
    {
        $serialized = config('session.serialization', 'php') === 'json' ? json_encode($data) : serialize($data);
        $payload = base64_encode(config('session.encrypt') ? Crypt::encrypt($serialized) : $serialized);
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => $payload, 'last_activity' => time()]);
    }
}
