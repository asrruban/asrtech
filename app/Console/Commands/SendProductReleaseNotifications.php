<?php

namespace App\Console\Commands;

use App\Services\ProductReleaseNotificationService;
use Illuminate\Console\Command;

class SendProductReleaseNotifications extends Command
{
    protected $signature = 'products:send-release-notifications';

    protected $description = 'Queue customer emails for newly published product releases';

    public function handle(ProductReleaseNotificationService $notifications): int
    {
        $count = $notifications->scheduleDue();
        $this->info("Queued {$count} product release notification batches.");

        return self::SUCCESS;
    }
}
