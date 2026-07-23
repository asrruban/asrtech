<?php

namespace App\Services;

use App\Jobs\SendProductReleaseNotifications;
use App\Models\ProductRelease;
use Throwable;

class ProductReleaseNotificationService
{
    public function schedule(ProductRelease $release): bool
    {
        $release->refresh();

        if (! $release->status
            || $release->released_at->isFuture()
            || ($release->available_until !== null && $release->available_until->isPast())
            || $release->notification_queued_at !== null
            || $release->notified_at !== null) {
            return false;
        }

        $claimed = ProductRelease::query()
            ->whereKey($release->id)
            ->whereNull('notification_queued_at')
            ->whereNull('notified_at')
            ->update(['notification_queued_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            SendProductReleaseNotifications::dispatch($release->id)->afterCommit();
        } catch (Throwable $exception) {
            ProductRelease::query()
                ->whereKey($release->id)
                ->whereNull('notified_at')
                ->update(['notification_queued_at' => null]);

            throw $exception;
        }

        return true;
    }

    public function scheduleDue(): int
    {
        $scheduled = 0;

        ProductRelease::query()
            ->where('status', true)
            ->where('released_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('available_until')
                    ->orWhere('available_until', '>', now());
            })
            ->whereNull('notification_queued_at')
            ->whereNull('notified_at')
            ->chunkById(100, function ($releases) use (&$scheduled): void {
                foreach ($releases as $release) {
                    $scheduled += $this->schedule($release) ? 1 : 0;
                }
            });

        return $scheduled;
    }
}
