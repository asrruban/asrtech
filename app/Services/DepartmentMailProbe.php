<?php

namespace App\Services;

use Illuminate\Support\Str;
use Throwable;

/**
 * Live POP3/IMAP login test for a support department's mail importing
 * configuration. Talks the wire protocols directly over a socket so it
 * works without the php-imap extension: ports 110/995 are probed as
 * POP3, everything else (143/993/custom) as IMAP; 993/995 use TLS.
 */
class DepartmentMailProbe
{
    private const TIMEOUT_SECONDS = 10;

    /** Returns null when login succeeds, otherwise a short error message. */
    public function test(string $hostname, int $port, string $email, string $password): ?string
    {
        $pop3 = in_array($port, [110, 995], true);
        $tls = in_array($port, [993, 995], true);

        try {
            $socket = stream_socket_client(
                ($tls ? 'ssl://' : 'tcp://')."{$hostname}:{$port}",
                $errorCode,
                $errorMessage,
                self::TIMEOUT_SECONDS,
            );
        } catch (Throwable $exception) {
            return Str::limit($exception->getMessage(), 200);
        }

        if ($socket === false) {
            return __('Could not connect to :host — :error', [
                'host' => "{$hostname}:{$port}",
                'error' => $errorMessage !== '' ? $errorMessage : "error {$errorCode}",
            ]);
        }

        stream_set_timeout($socket, self::TIMEOUT_SECONDS);

        try {
            return $pop3
                ? $this->loginPop3($socket, $email, $password)
                : $this->loginImap($socket, $email, $password);
        } finally {
            fclose($socket);
        }
    }

    /** @param  resource  $socket */
    private function loginPop3($socket, string $email, string $password): ?string
    {
        if (! str_starts_with((string) fgets($socket), '+OK')) {
            return __('The server did not send a POP3 greeting.');
        }

        foreach (["USER {$email}", "PASS {$password}"] as $command) {
            fwrite($socket, "{$command}\r\n");
            $reply = (string) fgets($socket);

            if (! str_starts_with($reply, '+OK')) {
                return __('Login failed: :reply', ['reply' => Str::limit(trim($reply) !== '' ? trim($reply) : 'no response', 150)]);
            }
        }

        fwrite($socket, "QUIT\r\n");

        return null;
    }

    /** @param  resource  $socket */
    private function loginImap($socket, string $email, string $password): ?string
    {
        if (! str_contains((string) fgets($socket), 'OK')) {
            return __('The server did not send an IMAP greeting.');
        }

        $quote = fn (string $value): string => '"'.addcslashes($value, '"\\').'"';
        fwrite($socket, 'a1 LOGIN '.$quote($email).' '.$quote($password)."\r\n");

        while (($line = fgets($socket)) !== false) {
            if (str_starts_with($line, 'a1 ')) {
                if (str_starts_with($line, 'a1 OK')) {
                    fwrite($socket, "a2 LOGOUT\r\n");

                    return null;
                }

                return __('Login failed: :reply', ['reply' => Str::limit(trim(substr($line, 3)), 150)]);
            }
        }

        return __('The server closed the connection during login.');
    }
}
