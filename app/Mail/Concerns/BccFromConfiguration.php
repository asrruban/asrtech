<?php

namespace App\Mail\Concerns;

trait BccFromConfiguration
{
    /**
     * WHMCS "BCC Messages": copy all system mail to these addresses.
     *
     * @return list<string>
     */
    protected function configuredBcc(): array
    {
        $raw = (string) config('asrtech.mail_bcc', '');

        return array_values(array_filter(array_map(
            static fn (string $address): string => trim($address),
            explode(',', $raw),
        ), static fn (string $address): bool => filter_var($address, FILTER_VALIDATE_EMAIL) !== false));
    }
}
