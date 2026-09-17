<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Generic TOTP (RFC 6238) two-factor service shared by admins and clients.
 */
class TwoFactorService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function uri(Admin|User $account, string $secret): string
    {
        $issuer = (string) config('app.name', 'ASRTech');
        $label = rawurlencode("{$issuer}:{$account->email}");

        return "otpauth://totp/{$label}?secret={$secret}&issuer=".rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public function code(string $secret, ?int $timestamp = null): string
    {
        return $this->codeForCounter($secret, intdiv($timestamp ?? time(), 30));
    }

    public function matchingCounter(string $secret, string $code, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            return null;
        }

        $counter = intdiv($timestamp ?? time(), 30);

        foreach ([$counter - 1, $counter, $counter + 1] as $candidate) {
            if ($candidate >= 0 && hash_equals($this->codeForCounter($secret, $candidate), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    public function verify(Admin|User $account, string $code, bool $allowRecovery = true): bool
    {
        if (! $account->hasTwoFactorEnabled() || ! is_string($account->two_factor_secret)) {
            return false;
        }

        $counter = $this->matchingCounter($account->two_factor_secret, $code);

        if ($counter !== null) {
            $updated = $account->newQuery()
                ->whereKey($account->getKey())
                ->where(function ($query) use ($counter) {
                    $query->whereNull('two_factor_last_counter')
                        ->orWhere('two_factor_last_counter', '<', $counter);
                })
                ->update(['two_factor_last_counter' => $counter]);

            if ($updated === 1) {
                $account->refresh();

                return true;
            }
        }

        return $allowRecovery && $this->consumeRecoveryCode($account, $code);
    }

    /** @return list<string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code): string => Hash::make($this->normalizeRecoveryCode($code)), $codes);
    }

    public function consumeRecoveryCode(Admin|User $account, string $code): bool
    {
        $normalized = $this->normalizeRecoveryCode($code);

        if ($normalized === '') {
            return false;
        }

        $consumed = DB::transaction(function () use ($account, $normalized): bool {
            $locked = $account->newQuery()->lockForUpdate()->find($account->getKey());

            if (! $locked instanceof Admin && ! $locked instanceof User) {
                return false;
            }

            if (! $locked->hasTwoFactorEnabled()) {
                return false;
            }

            $hashes = $locked->two_factor_recovery_codes ?? [];

            foreach ($hashes as $index => $hash) {
                if (Hash::check($normalized, $hash)) {
                    unset($hashes[$index]);
                    $locked->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                    return true;
                }
            }

            return false;
        });

        if ($consumed) {
            $account->refresh();
        }

        return $consumed;
    }

    private function codeForCounter(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $high = intdiv($counter, 4294967296);
        $low = $counter % 4294967296;
        $hash = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $value): string
    {
        $bits = '';

        foreach (str_split($value) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $alphabetIndex = (int) bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT));
            $encoded .= self::ALPHABET[$alphabetIndex];
        }

        return $encoded;
    }

    private function base32Decode(string $value): string
    {
        $bits = '';

        foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/i', '', $value) ?? '')) as $character) {
            $position = strpos(self::ALPHABET, $character);

            if ($position !== false) {
                $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
            }
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $byte = (int) bindec($chunk);

                if ($byte >= 0 && $byte <= 255) {
                    $decoded .= chr($byte);
                }
            }
        }

        return $decoded;
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code) ?? '');
    }
}
