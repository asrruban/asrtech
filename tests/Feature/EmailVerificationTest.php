<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_an_otp_and_redirects_to_verification(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Al Amin',
            'email' => 'alamin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/verify-email');

        $user = User::query()->where('email', 'alamin@example.com')->sole();
        $this->assertNull($user->email_verified_at);
        $this->assertSame(1, EmailOtp::query()->where('user_id', $user->id)->count());

        Mail::assertSent(EmailOtpMail::class, fn (EmailOtpMail $mail) => $mail->hasTo('alamin@example.com'));
    }

    public function test_unverified_users_cannot_reach_account_or_checkout(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/client-area')->assertRedirect('/verify-email');
    }

    public function test_a_valid_otp_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        EmailOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post('/verify-email', ['code' => '123456'])
            ->assertRedirect('/client-area');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame(0, EmailOtp::query()->count());
    }

    public function test_an_invalid_otp_is_rejected_and_counted(): void
    {
        $user = User::factory()->unverified()->create();
        $otp = EmailOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post('/verify-email', ['code' => '999999'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_an_expired_otp_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        EmailOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post('/verify-email', ['code' => '123456'])
            ->assertSessionHasErrors('code');
    }

    public function test_resend_issues_a_fresh_code(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        app(EmailOtpService::class)->issue($user);
        $firstOtpId = EmailOtp::query()->sole()->id;

        $this->actingAs($user)->post('/verify-email/resend')->assertRedirect('/verify-email');

        $this->assertSame(1, EmailOtp::query()->count());
        $this->assertNotSame($firstOtpId, EmailOtp::query()->sole()->id);
        Mail::assertSent(EmailOtpMail::class, 2);
    }

    public function test_verified_users_skip_the_notice_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verify-email')->assertRedirect('/client-area');
    }
}
