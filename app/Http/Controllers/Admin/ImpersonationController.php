<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $request->session()->put('impersonating_user_id', $user->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You are now browsing as :name.', ['name' => $user->name])]);

        return redirect()->route('account.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->session()->pull('impersonating_user_id');

        Auth::guard('web')->logout();

        $request->session()->regenerateToken();

        if (Auth::guard('admin')->check() && $userId !== null) {
            return redirect()->route('admin.users.show', $userId);
        }

        return redirect()->route('home');
    }
}
