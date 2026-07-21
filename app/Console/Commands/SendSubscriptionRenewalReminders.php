<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionRenewalReminderMail;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSubscriptionRenewalReminders extends Command
{
    protected $signature = 'subscriptions:send-renewal-reminders';

    protected $description = 'Email one reminder before each active subscription renewal period';

    public function handle(): int
    {
        if (! config('asrtech.subscriptions.reminders_enabled', false)) {
            $this->info('Subscription renewal reminders are disabled in General Configuration.');

            return self::SUCCESS;
        }

        $days = max(1, min(60, (int) config('asrtech.subscriptions.reminder_days', 7)));
        $sent = 0;
        $failed = 0;

        Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->where('cancel_at_period_end', false)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '>', now())
            ->where('current_period_end', '<=', now()->addDays($days))
            ->with(['user', 'product'])
            ->eachById(function (Subscription $subscription) use (&$sent, &$failed): void {
                $eventId = $this->eventId($subscription);
                $event = SubscriptionEvent::query()->firstOrCreate(
                    ['gateway' => 'system', 'gateway_event_id' => $eventId],
                    [
                        'subscription_id' => $subscription->id,
                        'event_type' => 'subscription.renewal_reminder_sent',
                        'payload' => ['period_end' => $subscription->current_period_end?->toIso8601String()],
                        'processed_at' => now(),
                    ],
                );

                if (! $event->wasRecentlyCreated) {
                    return;
                }

                try {
                    Mail::to($subscription->user->email)
                        ->send(new SubscriptionRenewalReminderMail($subscription));
                    $sent++;
                } catch (Throwable $exception) {
                    $event->delete();
                    report($exception);
                    $failed++;
                }
            });

        $this->info("Sent {$sent} subscription renewal reminder(s).");

        if ($failed > 0) {
            $this->warn("{$failed} reminder(s) could not be delivered and will be retried.");
        }

        return self::SUCCESS;
    }

    private function eventId(Subscription $subscription): string
    {
        return "renewal-reminder:{$subscription->id}:{$subscription->current_period_end?->timestamp}";
    }
}
