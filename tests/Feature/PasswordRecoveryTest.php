<?php

namespace Tests\Feature;

use App\Http\Controllers\Client\PasswordRecoveryController;
use App\Jobs\SendPasswordRecoveryLink;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_recovery_pages_are_available_and_tokens_are_not_leaked_in_referrers(): void
    {
        $this->get('/forgot-password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Client/Auth/ForgotPassword'));
        $this->get('/reset-password/sample-token?email=client%40example.test')
            ->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn (Assert $page) => $page->component('Client/Auth/ResetPassword')->where('token', 'sample-token')->where('email', 'client@example.test'));
    }

    public function test_known_and_unknown_accounts_receive_identical_responses_and_encrypted_queued_work(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.test'] as $email) {
            $this->post('/forgot-password', ['email' => $email])
                ->assertRedirect('/forgot-password')
                ->assertSessionHas('recovery_status', PasswordRecoveryController::REQUEST_MESSAGE)
                ->assertSessionHasNoErrors();
            Queue::assertPushed(SendPasswordRecoveryLink::class, fn ($job) => $job->email === $email && $job->connection === 'database');
        }
        Queue::assertPushed(SendPasswordRecoveryLink::class, 2);
        $this->assertInstanceOf(ShouldBeEncrypted::class, new SendPasswordRecoveryLink($user->email));
    }

    public function test_worker_sends_a_broker_token_and_throttles_repeated_requests(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $job = new SendPasswordRecoveryLink($user->email);
        $job->handle();
        $job->handle();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->assertTrue(Password::tokenExists($user, $notification->token));
            $this->assertNotSame($notification->token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
            $this->assertStringContainsString('/reset-password/'.$notification->token, $notification->toMail($user)->actionUrl);

            return true;
        });
        (new SendPasswordRecoveryLink('unknown@example.test'))->handle();
        Notification::assertCount(1);
    }

    public function test_transport_failure_clears_the_unused_token_so_the_job_can_retry(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        try {
            (new SendPasswordRecoveryLink($user->email))->handle();
            $this->fail('Expected delivery error.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Mail unavailable', $exception->getMessage());
        }
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_rotates_password_and_remember_token_preserves_two_factor_and_consumes_the_token(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token', 'two_factor_secret' => 'saved-secret', 'two_factor_confirmed_at' => now()]);
        $token = Password::createToken($user);
        $payload = $this->resetPayload($user, $token);
        $this->post('/reset-password', $payload)->assertRedirect('/login')->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertSame('saved-secret', $user->fresh()->two_factor_secret);
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => $payload['password']])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_reset_invalidates_existing_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'old-client-session', 'user_id' => $user->id, 'payload' => base64_encode(json_encode([auth('web')->getName() => $user->id])), 'last_activity' => time()]);
        $this->post('/reset-password', $this->resetPayload($user, Password::createToken($user)))->assertRedirect('/login');
        $this->assertDatabaseMissing('sessions', ['id' => 'old-client-session']);
    }

    public function test_invalid_expired_and_other_accounts_tokens_are_rejected_and_confirmation_is_required(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->resetPayload($other, $token))->assertSessionHasErrors('email');
        $this->post('/reset-password', [...$this->resetPayload($user, $token), 'password_confirmation' => 'wrong'])->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $this->post('/reset-password', $this->resetPayload($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_requests_are_validated_and_rate_limited(): void
    {
        Queue::fake();
        $this->post('/forgot-password', ['email' => 'invalid'])->assertSessionHasErrors('email');
        for ($i = 0; $i < 4; $i++) {
            $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect();
        }
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertTooManyRequests();
    }

    public function test_a_pending_two_factor_challenge_cannot_complete_after_a_password_reset(): void
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $oldFingerprint = hash('sha256', $user->password);
        $this->post('/reset-password', $this->resetPayload($user, Password::createToken($user)))->assertRedirect('/login');
        $this->withSession(['client.two_factor.id' => $user->id, 'client.two_factor.credentials' => $oldFingerprint])
            ->post('/two-factor-challenge', ['code' => $service->code($secret)])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function resetPayload(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'New!RecoveryPass123', 'password_confirmation' => 'New!RecoveryPass123'];
    }
}
