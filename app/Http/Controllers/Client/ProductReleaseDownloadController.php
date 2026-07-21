<?php

namespace App\Http\Controllers\Client;

use App\Enums\LicenseStatus;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\ProductRelease;
use App\Models\ProductReleaseDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductReleaseDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        License $license,
        ProductRelease $release,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($license->user_id === $user->id, 404);
        abort_unless($release->product_id === $license->product_id, 404);
        abort_unless($license->status === LicenseStatus::Active, 403, 'An active license is required.');
        abort_if($license->expires_at?->isPast(), 403, 'This license has expired.');
        abort_unless($release->isAvailable(), 404);
        abort_unless(Storage::disk($release->disk)->exists($release->file_path), 404);

        DB::transaction(function () use ($request, $user, $license, $release): void {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->id);
            $lockedRelease = ProductRelease::query()->lockForUpdate()->findOrFail($release->id);

            abort_unless($lockedLicense->user_id === $user->id, 404);
            abort_unless($lockedRelease->product_id === $lockedLicense->product_id, 404);
            abort_unless($lockedLicense->status === LicenseStatus::Active, 403, 'An active license is required.');
            abort_if($lockedLicense->expires_at?->isPast(), 403, 'This license has expired.');
            abort_unless($lockedRelease->isAvailable(), 404);

            $downloadsUsed = ProductReleaseDownload::query()
                ->where('product_release_id', $lockedRelease->id)
                ->where('license_id', $lockedLicense->id)
                ->count();

            abort_if(
                $lockedRelease->download_limit !== null
                    && $downloadsUsed >= $lockedRelease->download_limit,
                403,
                'The download limit for this release has been reached.',
            );

            ProductReleaseDownload::query()->create([
                'product_release_id' => $lockedRelease->id,
                'license_id' => $lockedLicense->id,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'downloaded_at' => now(),
            ]);
        });

        return Storage::disk($release->disk)->download(
            $release->file_path,
            $release->original_filename,
            [
                'Content-Type' => $release->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }
}
