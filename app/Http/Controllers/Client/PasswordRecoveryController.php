<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordRecoveryLink;
use App\Models\User;
use App\Services\CustomerSessionRevoker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class PasswordRecoveryController extends Controller
{
    public const REQUEST_MESSAGE = 'If an account matches that email address, a password reset link will be sent. Please check your inbox and spam folder.';

    public function create(Request $request): Response
    {
        return Inertia::render('Client/Auth/ForgotPassword', [
            'status' => $request->session()->get('recovery_status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:rfc', 'max:255']]);

        try {
            // Queue the same work for existing and unknown accounts. The broker
            // checks eligibility and per-account throttling inside the worker.
            SendPasswordRecoveryLink::dispatch($data['email'])
                ->onConnection((string) config('account_recovery.queue_connection', 'database'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Password recovery is temporarily unavailable. Please try again shortly.']);
        }

        return redirect()->route('password.request')->with('recovery_status', self::REQUEST_MESSAGE);
    }

    public function edit(Request $request, string $token): HttpResponse
    {
        $response = Inertia::render('Client/Auth/ResetPassword', [
            'token' => $token,
            'email' => is_string($request->query('email')) ? $request->query('email') : '',
        ])->toResponse($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = DB::transaction(function () use ($data) {
            // Serialize resets for a token so concurrent requests cannot both
            // pass the broker's single-use check before it deletes the token.
            DB::table((string) config('auth.passwords.users.table', 'password_reset_tokens'))
                ->where('email', $data['email'])->lockForUpdate()->first();

            return Password::broker('users')->reset($data, function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                app(CustomerSessionRevoker::class)->revoke($user);

                // Two-factor secrets and verified-email status are intentionally
                // preserved. Recovery never signs the customer in automatically.
                event(new PasswordReset($user));
            });
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => 'This reset link is invalid or has expired. Request a new link and try again.',
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Your password has been reset. Sign in with your new password.']);

        return redirect()->route('login');
    }
}
