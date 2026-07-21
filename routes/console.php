<?php

use App\Services\SubscriptionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:backfill', function (SubscriptionService $subscriptions) {
    $created = $subscriptions->backfillPaidOrders();
    $this->info("Created {$created} subscription records.");
})->purpose('Backfill subscriptions for existing paid recurring orders');

Schedule::call(fn () => app(SubscriptionService::class)->endDueSubscriptions())
    ->hourly()
    ->name('subscriptions:end-due')
    ->withoutOverlapping();

Schedule::command('subscriptions:send-renewal-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('invoices:send-reminders')->dailyAt('09:00');
