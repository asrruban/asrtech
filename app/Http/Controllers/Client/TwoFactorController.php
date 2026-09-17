<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\BuildsAccountSummary;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    use BuildsAccountSummary;

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $enabled = $user->hasTwoFactorEnabled();
        $pendingSecret = ! $enabled && is_string($user->two_factor_secret)
            ? $user->two_factor_secret
            : null;

        return Inertia::render('Client/Account/Security', [
            ...$this->accountSummary($user),
            'twoFactor' => [
                'enabled' => $enabled,
                'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                'pending_secret' => $pendingSecret,
                'setup_uri' => $pendingSecret !== null
                    ? $this->twoFactor->uri($user, $pendingSecret)
                    : null,
                'recovery_codes' => $request->session()->pull('client.recovery_codes'),
                'recovery_codes_remaining' => count($user->two_factor_recovery_codes ?? []),
            ],
            'hasPassword' => filled($user->password),
        ]);
    }

    public function setup(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages(['two_factor' => __('Two-factor authentication is already enabled.')]);
        }

        $user->forceFill([
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_counter' => null,
        ])->save();

        return redirect()->route('account.security');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        /** @var User $user */
        $user = $request->user();
        $secret = $user->two_factor_secret;
        $counter = is_string($secret)
            ? $this->twoFactor->matchingCounter($secret, $data['code'])
            : null;

        if ($counter === null || $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid.')]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_counter' => $counter,
        ])->save();
        $request->session()->flash('client.recovery_codes', $codes);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication enabled.')]);

        return redirect()->route('account.security');
    }

    public function disable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'current_password' => [Rule::requiredIf(filled($user->password)), 'nullable', 'current_password:web'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! $this->twoFactor->verify($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => __('The authentication or recovery code is invalid.')]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_counter' => null,
        ])->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication disabled.')]);

        return redirect()->route('account.security');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'current_password' => [Rule::requiredIf(filled($user->password)), 'nullable', 'current_password:web'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! $this->twoFactor->verify($user, $data['code'], false)) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid.')]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($codes)])->save();
        $request->session()->flash('client.recovery_codes', $codes);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('New recovery codes generated. Previous codes no longer work.')]);

        return redirect()->route('account.security');
    }
}
