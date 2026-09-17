<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('account.index');
        }

        if (! $request->session()->has('client.two_factor.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Client/Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);
        $userId = $request->session()->get('client.two_factor.id');
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        $credentials = $request->session()->get('client.two_factor.credentials');
        if (! $user instanceof User || ! is_string($credentials)
            || ! hash_equals(hash('sha256', (string) $user->getAuthPassword()), $credentials)) {
            $request->session()->forget('client.two_factor');

            return redirect()->route('login')->withErrors([
                'email' => 'Your sign-in session expired. Sign in again with your current password.',
            ]);
        }

        if (! $this->twoFactor->verify($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('The authentication or recovery code is invalid or has already been used.'),
            ]);
        }

        $remember = $request->session()->pull('client.two_factor.remember', false);
        $request->session()->forget('client.two_factor');
        Auth::guard('web')->login($user, (bool) $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }
}
