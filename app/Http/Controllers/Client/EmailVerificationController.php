<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailOtpService $otp) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('account.index');
        }

        return Inertia::render('Client/Auth/VerifyEmail', [
            'email' => $request->user()?->email,
            'ttlMinutes' => EmailOtpService::ttlMinutes(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! $this->otp->verify($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('The code is invalid or has expired. Request a new one.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email verified. Welcome aboard!')]);

        return redirect()->intended(route('account.index'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! $user->hasVerifiedEmail()) {
            $this->otp->issue($user);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('A new code is on its way to :email.', ['email' => $user->email])]);

        return redirect()->route('verification.notice');
    }
}
