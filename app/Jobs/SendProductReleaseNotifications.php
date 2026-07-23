<?php

namespace App\Jobs;

use App\Enums\LicenseStatus;
use App\Mail\ProductReleasePublishedMail;
use App\Models\License;
use App\Models\ProductRelease;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendProductReleaseNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $releaseId) {}

    public function handle(): void
    {
        $release = ProductRelease::query()
            ->with('product')
            ->find($this->releaseId);

        if ($release === null || $release->notified_at !== null) {
            return;
        }

        if (! $release->status
            || $release->released_at->isFuture()
            || ($release->available_until !== null && $release->available_until->isPast())) {
            $release->update(['notification_queued_at' => null]);

            return;
        }

        License::query()
            ->where('product_id', $release->product_id)
            ->where('status', LicenseStatus::Active)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('user:id,name,email')
            ->orderBy('id')
            ->get()
            ->unique('user_id')
            ->each(function (License $license) use ($release): void {
                if ($license->user === null) {
                    return;
                }

                Mail::to($license->user->email)->queue(
                    new ProductReleasePublishedMail($release, $license->user, $license),
                );
            });

        $release->update(['notified_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        ProductRelease::query()
            ->whereKey($this->releaseId)
            ->whereNull('notified_at')
            ->update(['notification_queued_at' => null]);
    }
}
