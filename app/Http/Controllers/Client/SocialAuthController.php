<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    /** @var list<string> */
    public const PROVIDERS = ['google', 'github'];

    /**
     * Providers with client credentials configured, shown as buttons.
     *
     * @return list<string>
     */
    public static function enabledProviders(): array
    {
        return array_values(array_filter(
            self::PROVIDERS,
            fn (string $provider): bool => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret")),
        ));
    }

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::enabledProviders(), true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::enabledProviders(), true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Sign in with :provider failed. Please try again.', ['provider' => Str::title($provider)]),
            ]);

            return redirect()->route('login');
        }

        $user = User::query()
            ->where('social_provider', $provider)
            ->where('social_provider_id', (string) $socialUser->getId())
            ->first();

        // Link by email when the account was created with a password first.
        $user ??= tap(
            User::query()->where('email', $socialUser->getEmail())->first(),
            fn (?User $existing) => $existing?->update([
                'social_provider' => $provider,
                'social_provider_id' => (string) $socialUser->getId(),
            ]),
        );

        if ($user === null && ! config('asrtech.allow_registration', true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Registration is currently disabled.')]);

            return redirect()->route('login');
        }

        $user ??= User::query()->create([
            'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Customer'),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'social_provider' => $provider,
            'social_provider_id' => (string) $socialUser->getId(),
        ]);

        // OAuth providers hand us verified email addresses.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }
}
