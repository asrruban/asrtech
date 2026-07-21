<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;

class LicenseVerificationService
{
    /**
     * Validate a license check from a product installation, WHMCS
     * licensing-addon style: status and expiry are checked first, the
     * first activation records the installation, and later checks must
     * match the recorded domain, IP, and directory. Every check is
     * written to the access log.
     *
     * @return array<string, mixed>
     */
    public function verify(string $key, ?string $domain, ?string $ip, ?string $path): array
    {
        $license = License::query()
            ->with('product:id,name,slug')
            ->where('license_key', $key)
            ->first();

        if ($license === null) {
            return ['status' => 'invalid', 'message' => 'Invalid License Key'];
        }

        $result = $this->evaluate($license, $domain, $ip, $path);

        $license->accessLogs()->create([
            'domain' => $domain,
            'ip_address' => $ip,
            'path' => $path,
            'result' => $result['message'],
        ]);

        $this->pruneLog($license);

        if ($result['status'] === 'active') {
            $result += [
                'product' => $license->product->name,
                'registered_at' => $license->created_at?->toDateString(),
                'expires_at' => $license->expires_at?->toDateString(),
            ];
        }

        return $result;
    }

    /**
     * Honor the configured activity log limit per license.
     */
    private function pruneLog(License $license): void
    {
        $limit = (int) config('asrtech.activity_log_limit', 1000);

        if ($limit < 1) {
            return;
        }

        $excess = $license->accessLogs()->count() - $limit;

        if ($excess > 0) {
            $license->accessLogs()
                ->orderBy('id')
                ->limit($excess)
                ->delete();
        }
    }

    /** @return array{status: string, message: string} */
    private function evaluate(License $license, ?string $domain, ?string $ip, ?string $path): array
    {
        if ($license->status === LicenseStatus::Terminated) {
            return ['status' => 'terminated', 'message' => 'License Terminated'];
        }

        if ($license->status === LicenseStatus::Suspended) {
            return ['status' => 'suspended', 'message' => 'License Suspended'];
        }

        if ($license->expires_at !== null && $license->expires_at->isPast()) {
            return ['status' => 'expired', 'message' => 'License Expired'];
        }

        // First activation: record where the license is being used.
        if ($license->validDomains() === [] && filled($domain)) {
            $license->update([
                'domain' => strtolower(trim((string) $domain)),
                'ip_address' => filled($ip) ? trim((string) $ip) : null,
                'path' => filled($path) ? trim((string) $path) : null,
            ]);

            return ['status' => 'active', 'message' => 'Valid (Installation Recorded)'];
        }

        if (filled($domain) && $license->validDomains() !== []
            && ! in_array(strtolower(trim((string) $domain)), $license->validDomains(), true)) {
            return ['status' => 'invalid', 'message' => 'Domain Invalid'];
        }

        if (filled($ip) && $license->validIps() !== []
            && ! in_array(strtolower(trim((string) $ip)), $license->validIps(), true)) {
            return ['status' => 'invalid', 'message' => 'IP Address Invalid'];
        }

        if (filled($path) && $license->validDirectories() !== []
            && ! in_array(strtolower(trim((string) $path)), $license->validDirectories(), true)) {
            return ['status' => 'invalid', 'message' => 'Directory Invalid'];
        }

        return ['status' => 'active', 'message' => 'Valid'];
    }
}
