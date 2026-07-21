<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * Generate a fresh verification code and email it to the user.
     */
    public function issue(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        EmailOtp::query()->where('user_id', $user->id)->delete();

        EmailOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::ttlMinutes()),
        ]);

        Mail::to($user->email)->send(new EmailOtpMail($user, $code));
    }

    public static function ttlMinutes(): int
    {
        return max(5, (int) config('asrtech.security.otp_ttl', self::TTL_MINUTES));
    }

    /**
     * Check the submitted code and mark the email verified on success.
     */
    public function verify(User $user, string $code): bool
    {
        $otp = EmailOtp::query()->where('user_id', $user->id)->latest('id')->first();

        if ($otp === null || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->delete();

        $user->forceFill(['email_verified_at' => now()])->save();

        return true;
    }
}
