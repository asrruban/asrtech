<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminAuditService;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AdminTwoFactorService $twoFactor,
        private readonly AdminAuditService $audit,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $request->session()->has('admin.two_factor.id')) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Admin/Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);
        $adminId = $request->session()->get('admin.two_factor.id');
        $admin = is_numeric($adminId) ? Admin::query()->find((int) $adminId) : null;

        if (! $admin instanceof Admin || ! $this->twoFactor->verify($admin, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('The authentication or recovery code is invalid or has already been used.'),
            ]);
        }

        $remember = $request->session()->pull('admin.two_factor.remember', false);
        $request->session()->forget('admin.two_factor.id');
        Auth::guard('admin')->login($admin, (bool) $remember);
        $request->session()->regenerate();
        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        $this->audit->record($admin, 'admin.two-factor.login', 'Completed two-factor admin sign-in', request: $request);

        return redirect()->intended(route('admin.dashboard'));
    }
}
