<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

class RememberClientPasswordAtLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User || ! request()->hasSession()) {
            return;
        }

        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard) {
            // Seed before the first protected request, including OAuth-only
            // customers whose password is currently null. A later reset must
            // not let an older session adopt the newly changed password hash.
            request()->session()->put('password_hash_web', $guard->hashPasswordForCookie((string) $event->user->getAuthPassword()));
        }
    }
}
