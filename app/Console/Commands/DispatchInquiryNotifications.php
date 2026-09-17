<?php

namespace App\Console\Commands;

use App\Models\InquiryNotificationDelivery;
use App\Services\InquiryNotificationService;
use Illuminate\Console\Command;

class DispatchInquiryNotifications extends Command
{
    protected $signature = 'inquiry-notifications:dispatch';

    protected $description = 'Recover pending inquiry notifications that could not be queued or whose workers stopped';

    public function handle(InquiryNotificationService $notifications): int
    {
        $configuration = $notifications->configuration();
        if (! $configuration['enabled'] || ! $configuration['ready']) {
            $this->info('Inquiry notifications are disabled or need configuration.');

            return self::SUCCESS;
        }

        $count = 0;
        InquiryNotificationDelivery::query()->whereNull('sent_at')
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->where('attempts', '<', 5)
            ->where(fn ($query) => $query->whereNull('queued_at')->orWhere('queued_at', '<', now()->subHours(2)))
            ->chunkById(100, function ($deliveries) use ($notifications, &$count): void {
                foreach ($deliveries as $delivery) {
                    if ($notifications->enqueue($delivery)) {
                        $count++;
                    }
                }
            });
        $this->info("Queued {$count} inquiry notifications.");

        return self::SUCCESS;
    }
}
