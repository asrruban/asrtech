<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStorageSettingsRequest;
use App\Services\SettingService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Upload storage configuration: pick the disk backing the `uploads`
 * filesystem (local, Amazon S3, Cloudflare R2, Backblaze B2) and the
 * directories used for branding, support-ticket, and product files.
 */
class StorageSettingController extends Controller
{
    /** @var array<string, string> Driver key => its write-only secret setting. */
    private const DRIVER_SECRETS = [
        's3' => 'storage_s3_secret',
        'r2' => 'storage_r2_secret',
        'b2' => 'storage_b2_key',
    ];

    public function __construct(private readonly SettingService $settings) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Configuration/Settings/Storage', [
            'drivers' => StorageService::DRIVERS,
            'settings' => [
                'storage_driver' => $this->settings->get('storage_driver', 'local'),

                'storage_s3_key' => $this->settings->get('storage_s3_key'),
                'storage_s3_secret_configured' => filled($this->settings->get('storage_s3_secret')),
                'storage_s3_region' => $this->settings->get('storage_s3_region'),
                'storage_s3_bucket' => $this->settings->get('storage_s3_bucket'),
                'storage_s3_url' => $this->settings->get('storage_s3_url'),

                'storage_r2_account_id' => $this->settings->get('storage_r2_account_id'),
                'storage_r2_key' => $this->settings->get('storage_r2_key'),
                'storage_r2_secret_configured' => filled($this->settings->get('storage_r2_secret')),
                'storage_r2_bucket' => $this->settings->get('storage_r2_bucket'),
                'storage_r2_url' => $this->settings->get('storage_r2_url'),

                'storage_b2_key_id' => $this->settings->get('storage_b2_key_id'),
                'storage_b2_key_configured' => filled($this->settings->get('storage_b2_key')),
                'storage_b2_region' => $this->settings->get('storage_b2_region'),
                'storage_b2_bucket' => $this->settings->get('storage_b2_bucket'),
                'storage_b2_url' => $this->settings->get('storage_b2_url'),

                'storage_path_branding' => $this->settings->get('storage_path_branding', StorageService::DEFAULT_PATHS['branding']),
                'storage_path_tickets' => $this->settings->get('storage_path_tickets', StorageService::DEFAULT_PATHS['tickets']),
                'storage_path_products' => $this->settings->get('storage_path_products', StorageService::DEFAULT_PATHS['products']),
            ],
        ]);
    }

    public function update(SaveStorageSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $driver = $data['storage_driver'];

        $secretKey = self::DRIVER_SECRETS[$driver] ?? null;

        if ($secretKey !== null
            && blank($data[$secretKey] ?? null)
            && blank($this->settings->get($secretKey))) {
            throw ValidationException::withMessages([
                $secretKey => __('This field is required for the selected storage driver.'),
            ]);
        }

        foreach ([
            'storage_driver',
            'storage_s3_key',
            'storage_s3_region',
            'storage_s3_bucket',
            'storage_s3_url',
            'storage_r2_account_id',
            'storage_r2_key',
            'storage_r2_bucket',
            'storage_r2_url',
            'storage_b2_key_id',
            'storage_b2_region',
            'storage_b2_bucket',
            'storage_b2_url',
            'storage_path_branding',
            'storage_path_tickets',
            'storage_path_products',
        ] as $key) {
            $this->settings->put($key, isset($data[$key]) ? (string) $data[$key] : null);
        }

        // Write-only secrets: keep the stored value when left blank.
        foreach (self::DRIVER_SECRETS as $secret) {
            if (filled($data[$secret] ?? null)) {
                $this->settings->put($secret, (string) $data[$secret]);
            }
        }

        // The probe performs a live network round-trip — skipped in tests.
        $error = $driver === 'local' || app()->runningUnitTests()
            ? null
            : $this->probe();

        Inertia::flash('toast', $error === null
            ? ['type' => 'success', 'message' => __('Storage settings saved.')]
            : ['type' => 'error', 'message' => __('Settings saved, but the connection test failed: :error', ['error' => $error])]);

        return redirect()->route('admin.settings.storage.edit');
    }

    /** Write and delete a probe file on the newly configured disk. */
    private function probe(): ?string
    {
        try {
            $disk = Storage::build([
                ...StorageService::diskConfig($this->settings->all()),
                'throw' => true,
            ]);

            $probe = 'asrtech-probe-'.Str::random(8).'.txt';
            $disk->put($probe, 'ASRTech storage connection test');
            $disk->delete($probe);

            return null;
        } catch (Throwable $exception) {
            return Str::limit($exception->getMessage(), 200);
        }
    }
}
