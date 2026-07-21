<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly EmailOtpService $otp) {}

    public function create(): Response
    {
        return Inertia::render('Client/Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function createRegister(): Response|RedirectResponse
    {
        if (! config('asrtech.allow_registration', true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Registration is currently disabled.')]);

            return redirect()->route('login');
        }

        return Inertia::render('Client/Auth/Register');
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        abort_unless((bool) config('asrtech.allow_registration', true), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ...(config('asrtech.require_tos_accept', false) ? ['terms' => ['accepted']] : []),
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $this->otp->issue($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Almost there — we emailed a verification code to :email.', ['email' => $user->email])]);

        return redirect()->route('verification.notice');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
