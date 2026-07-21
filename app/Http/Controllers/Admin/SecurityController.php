<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function index(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $enabled = $admin->hasTwoFactorEnabled();
        $pendingSecret = ! $enabled && is_string($admin->two_factor_secret)
            ? $admin->two_factor_secret
            : null;
        $canManageAdmins = $admin->hasPermission('admins.manage');

        return Inertia::render('Admin/Security/Index', [
            'twoFactor' => [
                'enabled' => $enabled,
                'confirmed_at' => $admin->two_factor_confirmed_at,
                'pending_secret' => $pendingSecret,
                'setup_uri' => $pendingSecret !== null
                    ? $this->twoFactor->uri($admin, $pendingSecret)
                    : null,
                'recovery_codes' => $request->session()->pull('admin.recovery_codes'),
                'recovery_codes_remaining' => count($admin->two_factor_recovery_codes ?? []),
            ],
            'canManageAdmins' => $canManageAdmins,
            'roleOptions' => $canManageAdmins
                ? collect(AdminRole::cases())->map(fn (AdminRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'permissions' => $role->permissions(),
                ])
                : [],
            'admins' => $canManageAdmins
                ? Admin::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Admin $item): array => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'email' => $item->email,
                        'role' => $item->role->value,
                        'role_label' => $item->role->label(),
                        'two_factor_enabled' => $item->hasTwoFactorEnabled(),
                        'last_login_at' => $item->last_login_at,
                        'last_login_ip' => $item->last_login_ip,
                        'is_current' => $item->is($admin),
                    ])
                : [],
            'auditLogs' => $canManageAdmins
                ? AdminAuditLog::query()
                    ->with('admin:id,name,email')
                    ->latest('id')
                    ->paginate((int) config('asrtech.records_per_page', 15))
                    ->through(fn (AdminAuditLog $log): array => [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $log->description,
                        'subject_type' => $log->subject_type,
                        'subject_id' => $log->subject_id,
                        'metadata' => $log->metadata,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at,
                        'admin' => $log->admin?->only(['id', 'name', 'email']),
                    ])
                : null,
        ]);
    }

    public function setup(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        if ($admin->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages(['two_factor' => __('Two-factor authentication is already enabled.')]);
        }

        $admin->update([
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_counter' => null,
        ]);

        return redirect()->route('admin.security.index');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $secret = $admin->two_factor_secret;
        $counter = is_string($secret)
            ? $this->twoFactor->matchingCounter($secret, $data['code'])
            : null;

        if ($counter === null || $admin->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid.')]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $admin->update([
            'two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_counter' => $counter,
        ]);
        $request->session()->flash('admin.recovery_codes', $codes);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication enabled.')]);

        return redirect()->route('admin.security.index');
    }

    public function disable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        /** @var Admin $admin */
        $admin = $request->user('admin');

        if (! $this->twoFactor->verify($admin, $data['code'])) {
            throw ValidationException::withMessages(['code' => __('The authentication or recovery code is invalid.')]);
        }

        $admin->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_counter' => null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication disabled.')]);

        return redirect()->route('admin.security.index');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        /** @var Admin $admin */
        $admin = $request->user('admin');

        if (! $this->twoFactor->verify($admin, $data['code'], false)) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid.')]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $admin->update(['two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($codes)]);
        $request->session()->flash('admin.recovery_codes', $codes);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('New recovery codes generated. Previous codes no longer work.')]);

        return redirect()->route('admin.security.index');
    }

    public function updateRole(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(AdminRole::class)],
        ]);
        /** @var Admin $actor */
        $actor = $request->user('admin');

        if ($admin->is($actor)) {
            throw ValidationException::withMessages(['role' => __('You cannot change your own administrator role.')]);
        }

        $role = AdminRole::from($data['role']);

        if ($admin->role === AdminRole::SuperAdmin
            && $role !== AdminRole::SuperAdmin
            && Admin::query()->where('role', AdminRole::SuperAdmin->value)->count() <= 1) {
            throw ValidationException::withMessages(['role' => __('At least one super administrator must remain.')]);
        }

        $admin->update(['role' => $role]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Administrator role updated.')]);

        return redirect()->route('admin.security.index');
    }
}
