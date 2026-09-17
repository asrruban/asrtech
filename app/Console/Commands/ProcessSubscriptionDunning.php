<?php

namespace App\Console\Commands;

use App\Services\DunningService;
use Illuminate\Console\Command;

class ProcessSubscriptionDunning extends Command
{
    protected $signature = 'subscriptions:process-dunning';

    protected $description = 'Send escalating dunning reminders to past-due subscriptions';

    public function handle(DunningService $dunning): int
    {
        $result = $dunning->process();

        $this->info("Dunning reminders sent: {$result['reminded']} (skipped {$result['skipped']}).");

        return self::SUCCESS;
    }
}
