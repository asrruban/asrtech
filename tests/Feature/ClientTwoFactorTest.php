<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_enable_two_factor_and_sees_recovery_codes(): void
    {
        $user = User::factory()->create();
        $service = app(TwoFactorService::class);

        $this->actingAs($user)
            ->post('/client-area/security/two-factor/setup')
            ->assertRedirect('/client-area/security');

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $code = $service->code($user->two_factor_secret);

        $this->actingAs($user)
            ->post('/client-area/security/two-factor/confirm', ['code' => $code])
            ->assertRedirect('/client-area/security');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes ?? []);
    }

    public function test_login_with_two_factor_requires_challenge(): void
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/two-factor-challenge');

        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->post('/two-factor-challenge', ['code' => $service->code($secret)])
            ->assertRedirect('/client-area');

        $this->assertAuthenticatedAs($user);
    }

    public function test_client_can_disable_two_factor_with_password_and_code(): void
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete('/client-area/security/two-factor', [
                'current_password' => 'password',
                'code' => $service->code($secret),
            ])
            ->assertRedirect('/client-area/security');

        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
    }

    public function test_recovery_code_can_complete_challenge_once(): void
    {
        $service = app(TwoFactorService::class);
        $codes = $service->generateRecoveryCodes();
        $user = User::factory()->create([
            'two_factor_secret' => $service->generateSecret(),
            'two_factor_recovery_codes' => $service->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post('/two-factor-challenge', ['code' => $codes[0]])
            ->assertRedirect('/client-area');

        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, $user->refresh()->two_factor_recovery_codes ?? []);
    }
}
