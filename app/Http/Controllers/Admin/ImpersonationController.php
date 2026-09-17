<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * WHMCS-style "Login as Client": the admin and client sessions use
 * separate guards, so signing the client in on the web guard leaves
 * the admin session intact and the admin can hop back at any time.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        Auth::guard('web')->login($user);
        // The web Login listener stamps this customer's auth.session
        // fingerprint while preserving the separate admin session.
        $request->session()->put('impersonating_user_id', $user->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You are now browsing as :name.', ['name' => $user->name])]);

        return redirect()->route('account.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->session()->pull('impersonating_user_id');

        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard) {
            $guard->logoutCurrentDevice();
        } else {
            $guard->logout();
        }
        $request->session()->forget('password_hash_web');

        $request->session()->regenerateToken();

        if (Auth::guard('admin')->check() && $userId !== null) {
            return redirect()->route('admin.users.show', $userId);
        }

        return redirect()->route('home');
    }
}
