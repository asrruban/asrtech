<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerSessionRevoker
{
    public function revoke(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $web = Auth::guard('web');
        $admin = Auth::guard('admin');
        if (! $web instanceof SessionGuard || ! $admin instanceof SessionGuard) {
            return;
        }

        $sessions = DB::connection(config('session.connection'))->table((string) config('session.table', 'sessions'));
        // The indexed user_id is only a candidate: admin and customer IDs may
        // overlap because both guards share this table. Inspect the web guard
        // identity before revoking anything. Other-driver and dual-guard
        // sessions are also checked by the login-time auth.session fingerprint.
        $sessions->where('user_id', $user->id)->chunkById(100, function ($rows) use ($user, $web, $admin): void {
            foreach ($rows as $row) {
                $data = $this->decode($row->payload);
                if ($data === null || (string) ($data[$web->getName()] ?? '') !== (string) $user->id) {
                    continue;
                }

                $target = DB::connection(config('session.connection'))->table((string) config('session.table', 'sessions'))
                    ->where('id', $row->id)->where('payload', $row->payload);
                if (! isset($data[$admin->getName()])) {
                    $target->delete();

                    continue;
                }

                // A simultaneous admin session survives; only the customer
                // identity and impersonation state are revoked.
                unset($data[$web->getName()], $data['password_hash_web'], $data['impersonating_user_id'], $data['client']['two_factor']);
                $target->update(['payload' => $this->encode($data), 'user_id' => $data[$admin->getName()]]);
            }
        });
    }

    /** @return array<string, mixed>|null */
    private function decode(string $payload): ?array
    {
        try {
            $serialized = base64_decode($payload, true);
            if ($serialized === false) {
                return null;
            }
            if (config('session.encrypt')) {
                $serialized = Crypt::decrypt($serialized);
            }
            if (! is_string($serialized)) {
                return null;
            }
            $data = config('session.serialization', 'php') === 'json'
                ? json_decode($serialized, true, 512, JSON_THROW_ON_ERROR)
                : @unserialize($serialized, ['allowed_classes' => false]);

            return is_array($data) ? $data : null;
        } catch (Throwable) {
            // Do not erase an unreadable session whose guard is unknown.
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        $serialized = config('session.serialization', 'php') === 'json'
            ? json_encode($data, JSON_THROW_ON_ERROR)
            : serialize($data);

        return base64_encode(config('session.encrypt') ? Crypt::encrypt($serialized) : $serialized);
    }
}
