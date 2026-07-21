<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin && $admin->hasTwoFactorEnabled()) {
            $adminId = $admin->id;
            $remember = $request->boolean('remember');
            Auth::guard('admin')->logout();
            $request->session()->regenerate();
            $request->session()->put([
                'admin.two_factor.id' => $adminId,
                'admin.two_factor.remember' => $remember,
            ]);

            return redirect()->route('admin.two-factor.challenge');
        }

        if ($admin instanceof Admin) {
            $admin->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
            $this->audit->record($admin, 'admin.login', 'Signed in to the admin console', request: $request);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
