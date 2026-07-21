<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-configurable upload storage: the `uploads` disk is rebuilt at boot
 * from the configuration table (local, Amazon S3, Cloudflare R2, or
 * Backblaze B2 — the latter three via the S3-compatible driver).
 */
class StorageService
{
    /** @var array<string, string> Driver key => label. */
    public const DRIVERS = [
        'local' => 'Local storage',
        's3' => 'Amazon S3 (AWS)',
        'r2' => 'Cloudflare R2',
        'b2' => 'Backblaze B2',
    ];

    /** @var array<string, string> Upload purpose => default directory. */
    public const DEFAULT_PATHS = [
        'branding' => 'branding',
        'tickets' => 'support/tickets',
        'products' => 'products',
    ];

    public function driver(): string
    {
        return (string) config('asrtech.storage.driver', 'local');
    }

    public function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk('uploads');
    }

    public function pathFor(string $purpose): string
    {
        return (string) config(
            "asrtech.storage.paths.{$purpose}",
            self::DEFAULT_PATHS[$purpose] ?? $purpose,
        );
    }

    /** Store an upload under the configured directory and return its public URL. */
    public function storeUpload(UploadedFile $file, string $purpose): string
    {
        $path = (string) $this->disk()->putFile($this->pathFor($purpose), $file, 'public');

        return $this->url($path);
    }

    public function url(string $path): string
    {
        // Local storage keeps site-relative /storage URLs so assets follow
        // the app domain; cloud drivers return their absolute URL.
        if ($this->driver() === 'local') {
            return '/storage/'.ltrim($path, '/');
        }

        return $this->disk()->url($path);
    }

    /**
     * Build the `uploads` disk configuration from raw configuration-table
     * values. Shared by boot-time hydration and the save-time probe.
     *
     * @param  array<string, string|null>  $values
     * @return array<string, mixed>
     */
    public static function diskConfig(array $values): array
    {
        return match (Arr::get($values, 'storage_driver', 'local')) {
            's3' => [
                'driver' => 's3',
                'key' => Arr::get($values, 'storage_s3_key'),
                'secret' => Arr::get($values, 'storage_s3_secret'),
                'region' => Arr::get($values, 'storage_s3_region'),
                'bucket' => Arr::get($values, 'storage_s3_bucket'),
                'url' => Arr::get($values, 'storage_s3_url') ?: null,
                'throw' => false,
                'report' => false,
            ],
            'r2' => [
                'driver' => 's3',
                'key' => Arr::get($values, 'storage_r2_key'),
                'secret' => Arr::get($values, 'storage_r2_secret'),
                'region' => 'auto',
                'bucket' => Arr::get($values, 'storage_r2_bucket'),
                'endpoint' => sprintf(
                    'https://%s.r2.cloudflarestorage.com',
                    Arr::get($values, 'storage_r2_account_id'),
                ),
                'use_path_style_endpoint' => true,
                'url' => Arr::get($values, 'storage_r2_url') ?: null,
                'throw' => false,
                'report' => false,
            ],
            'b2' => [
                'driver' => 's3',
                'key' => Arr::get($values, 'storage_b2_key_id'),
                'secret' => Arr::get($values, 'storage_b2_key'),
                'region' => Arr::get($values, 'storage_b2_region'),
                'bucket' => Arr::get($values, 'storage_b2_bucket'),
                'endpoint' => sprintf(
                    'https://s3.%s.backblazeb2.com',
                    Arr::get($values, 'storage_b2_region'),
                ),
                'use_path_style_endpoint' => true,
                'url' => Arr::get($values, 'storage_b2_url') ?: null,
                'throw' => false,
                'report' => false,
            ],
            default => [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim((string) config('app.url'), '/').'/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
        };
    }
}
