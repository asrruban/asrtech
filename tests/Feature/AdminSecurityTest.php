<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Services\AdminAuditService;
use App\Services\AdminTwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_totp_codes_match_the_rfc_test_secret(): void
    {
        $twoFactor = app(AdminTwoFactorService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $twoFactor->code($secret, 59));
        $this->assertSame(1, $twoFactor->matchingCounter($secret, '287082', 59));
        $this->assertNull($twoFactor->matchingCounter($secret, 'not-a-code', 59));
    }

    public function test_enabled_two_factor_is_required_after_a_valid_password_and_totp_cannot_be_replayed(): void
    {
        $twoFactor = app(AdminTwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $admin = $this->admin([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ]);
        $code = $twoFactor->code($secret);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])->assertRedirect('/admin/two-factor-challenge')
            ->assertSessionHas('admin.two_factor.id', $admin->id);

        $this->assertGuest('admin');

        $this->post('/admin/two-factor-challenge', ['code' => $code])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->refresh()->last_login_at);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'admin.two-factor.login',
        ]);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])->assertRedirect('/admin/two-factor-challenge');

        $this->from('/admin/two-factor-challenge')
            ->post('/admin/two-factor-challenge', ['code' => $code])
            ->assertRedirect('/admin/two-factor-challenge')
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_a_recovery_code_can_sign_in_only_once(): void
    {
        $twoFactor = app(AdminTwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $recoveryCode = 'ABCD-EFGH-2345';
        $admin = $this->admin([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes([$recoveryCode]),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])->assertRedirect('/admin/two-factor-challenge');

        $this->post('/admin/two-factor-challenge', ['code' => strtolower($recoveryCode)])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame([], $admin->refresh()->two_factor_recovery_codes);

        $this->post('/admin/logout');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ]);

        $this->from('/admin/two-factor-challenge')
            ->post('/admin/two-factor-challenge', ['code' => $recoveryCode])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_enable_two_factor_and_receives_one_time_recovery_codes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post('/admin/security/two-factor/setup')
            ->assertRedirect('/admin/security');

        $admin->refresh();
        $secret = $admin->two_factor_secret;
        $this->assertIsString($secret);
        $this->assertNotSame(
            $secret,
            DB::table('admins')->where('id', $admin->id)->value('two_factor_secret'),
        );

        $code = app(AdminTwoFactorService::class)->code($secret);

        $this->post('/admin/security/two-factor/confirm', ['code' => $code])
            ->assertRedirect('/admin/security');

        $this->get('/admin/security')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Security/Index')
                ->where('twoFactor.enabled', true)
                ->has('twoFactor.recovery_codes', 8)
                ->where('twoFactor.recovery_codes_remaining', 8)
                ->where('canManageAdmins', true));

        $this->assertNotNull($admin->refresh()->two_factor_confirmed_at);
        $this->assertCount(8, $admin->two_factor_recovery_codes ?? []);

        $audit = AdminAuditLog::query()
            ->where('action', 'admin.security.two-factor.confirm')
            ->firstOrFail();

        $this->assertSame('[REDACTED]', $audit->metadata['code'] ?? null);

        $this->get('/admin/security')
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactor.recovery_codes', null));
    }

    public function test_roles_enforce_least_privilege_and_hide_security_audit_data(): void
    {
        $billing = $this->admin(['email' => 'billing@example.com', 'role' => AdminRole::Billing]);
        $this->actingAs($billing, 'admin');

        $this->get('/admin/payments')->assertOk();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/settings/general')->assertForbidden();

        $this->get('/admin/security')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canManageAdmins', false)
                ->where('admins', [])
                ->where('roleOptions', [])
                ->where('auditLogs', null));

        $this->actingAs($this->admin([
            'email' => 'catalog@example.com',
            'role' => AdminRole::Catalog,
        ]), 'admin');

        $this->get('/admin/products')->assertOk();
        $this->get('/admin/payments')->assertForbidden();
    }

    public function test_super_admin_can_assign_roles_and_mutations_are_audited(): void
    {
        $actor = $this->admin(['email' => 'owner@example.com']);
        $target = $this->admin(['email' => 'staff@example.com']);
        $this->actingAs($actor, 'admin');

        $this->patch("/admin/security/admins/{$target->id}/role", [
            'role' => AdminRole::Support->value,
        ])->assertRedirect('/admin/security');

        $this->assertSame(AdminRole::Support, $target->refresh()->role);

        $audit = AdminAuditLog::query()
            ->where('action', 'admin.security.admins.role')
            ->firstOrFail();

        $this->assertSame($target->id, $audit->subject_id);
        $this->assertSame(AdminRole::Support->value, $audit->metadata['role'] ?? null);

        $this->from('/admin/security')
            ->patch("/admin/security/admins/{$actor->id}/role", [
                'role' => AdminRole::Billing->value,
            ])->assertRedirect('/admin/security')
            ->assertSessionHasErrors('role');

        $this->assertSame(AdminRole::SuperAdmin, $actor->refresh()->role);
    }

    public function test_audit_metadata_redacts_secrets_and_normalizes_file_uploads(): void
    {
        $metadata = app(AdminAuditService::class)->sanitize([
            'password' => 'do-not-store-this',
            'featured_image' => UploadedFile::fake()->image('product.png', 20, 20),
        ]);

        $this->assertSame('[REDACTED]', $metadata['password']);
        $this->assertSame('product.png', $metadata['featured_image']['file_name'] ?? null);
        $this->assertArrayHasKey('size', $metadata['featured_image']);
        $this->assertIsString(json_encode($metadata, JSON_THROW_ON_ERROR));
    }

    public function test_security_page_requires_admin_authentication(): void
    {
        $this->get('/admin/security')->assertRedirect('/admin/login');
        $this->post('/admin/security/two-factor/setup')->assertRedirect('/admin/login');
    }

    /** @param array<string, mixed> $attributes */
    private function admin(array $attributes = []): Admin
    {
        return Admin::query()->create(array_merge([
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            'password' => 'a-secure-password',
        ], $attributes));
    }
}
