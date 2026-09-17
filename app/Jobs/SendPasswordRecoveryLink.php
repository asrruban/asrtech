<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;
use Throwable;

class SendPasswordRecoveryLink implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $email) {}

    public function handle(): void
    {
        try {
            Password::broker('users')->sendResetLink(['email' => $this->email]);
        } catch (Throwable $exception) {
            // A transport error must not leave a recent token that causes the
            // next job attempt to be silently throttled instead of delivered.
            $user = User::query()->where('email', $this->email)->first();
            if ($user !== null) {
                Password::broker('users')->deleteToken($user);
            }

            throw $exception;
        }
    }
}
