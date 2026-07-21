<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Stringable;
use UnitEnum;

class AdminAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Admin $admin,
        string $action,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
        ?Request $request = null,
    ): AdminAuditLog {
        $request ??= request();

        return AdminAuditLog::query()->create([
            'admin_id' => $admin->id,
            'action' => Str::limit($action, 255, ''),
            'description' => Str::limit($description, 255, ''),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    public function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            $normalized = strtolower((string) $key);

            if ($this->sensitive($normalized)) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            $values[$key] = $this->sanitizeValue($value);
        }

        return $values;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitize($value);
        }

        if ($value instanceof UploadedFile) {
            $size = $value->getSize();

            return [
                'file_name' => Str::limit($value->getClientOriginalName(), 255, ''),
                'mime_type' => Str::limit($value->getClientMimeType(), 100, ''),
                'size' => is_int($size) ? $size : null,
            ];
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Stringable) {
            return Str::limit((string) $value, 1000);
        }

        if (is_string($value)) {
            return Str::limit($value, 1000);
        }

        if (is_float($value) && ! is_finite($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            return ['type' => class_basename($value)];
        }

        if (is_resource($value)) {
            return '[RESOURCE]';
        }

        return $value;
    }

    private function sensitive(string $key): bool
    {
        return in_array($key, [
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'code',
            'secret',
            'two_factor_secret',
            'recovery_code',
            'client_secret',
            'api_key',
            'token',
            'smtp_password',
            'mail_password',
        ], true) || str_ends_with($key, '_secret') || str_ends_with($key, '_password');
    }
}
