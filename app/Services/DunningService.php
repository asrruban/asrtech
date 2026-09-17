<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionDunningReminderMail;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Dunning ladder: send escalating reminders to past-due subscribers at
 * configurable intervals after the failed payment. Access expiry itself is
 * handled by SubscriptionService::endDueSubscriptions() (grace period).
 */
class DunningService
{
    /** @return array{reminded: int, skipped: int} */
    public function process(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        if (! (bool) config('asrtech.dunning.enabled', true)) {
            return ['reminded' => 0, 'skipped' => 0];
        }

        /** @var list<int> $ladder */
        $ladder = array_values(array_map('intval', (array) config('asrtech.dunning.reminder_days', [1, 3, 7])));
        sort($ladder);

        if ($ladder === []) {
            return ['reminded' => 0, 'skipped' => 0];
        }

        $reminded = 0;
        $skipped = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::PastDue)
            ->with(['latestPaymentFailure', 'user', 'product'])
            ->eachById(function (Subscription $subscription) use ($ladder, $now, &$reminded, &$skipped): void {
                $failure = $subscription->latestPaymentFailure;

                if ($failure === null) {
                    $skipped++;

                    return;
                }

                $daysSinceFailure = $failure->processed_at->diffInDays($now);

                // How many ladder rungs are currently due?
                $dueCount = 0;

                foreach ($ladder as $day) {
                    if ($daysSinceFailure >= $day) {
                        $dueCount++;
                    }
                }

                if ($dueCount <= $subscription->dunning_reminders_sent) {
                    $skipped++;

                    return;
                }

                $attempt = $subscription->dunning_reminders_sent + 1;
                $isFinal = $attempt >= count($ladder);
                $daysUntilSuspension = $this->daysUntilSuspension($subscription, $now);

                try {
                    Mail::to($subscription->user->email)->send(
                        new SubscriptionDunningReminderMail($subscription, $attempt, $isFinal, $daysUntilSuspension),
                    );
                } catch (Throwable $exception) {
                    // Dunning runs hourly; a transient mail failure must not
                    // mark the reminder as sent — retry on the next run.
                    report($exception);

                    return;
                }

                $subscription->newQuery()
                    ->whereKey($subscription->id)
                    ->where('dunning_reminders_sent', '<', $attempt)
                    ->update(['dunning_reminders_sent' => $attempt]);

                $reminded++;
            });

        return ['reminded' => $reminded, 'skipped' => $skipped];
    }

    private function daysUntilSuspension(Subscription $subscription, CarbonImmutable $now): ?int
    {
        if ($subscription->current_period_end === null) {
            return null;
        }

        $graceDays = (int) config('asrtech.subscriptions.grace_days', 3);
        $expiresAt = CarbonImmutable::instance($subscription->current_period_end)->addDays($graceDays);

        return max(0, (int) ceil($now->diffInDays($expiresAt)));
    }
}
