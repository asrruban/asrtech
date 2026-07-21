<?php

namespace App\Services;

use App\Mail\SubscriptionCancellationScheduledMail;
use App\Mail\SubscriptionPaymentFailedMail;
use App\Mail\SubscriptionRenewedMail;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionNotificationService
{
    public function cancellationScheduled(Subscription $subscription): void
    {
        $this->send($subscription, new SubscriptionCancellationScheduledMail($subscription));
    }

    public function renewed(Subscription $subscription, Order $renewalOrder): void
    {
        $this->send($subscription, new SubscriptionRenewedMail($subscription, $renewalOrder));
    }

    public function paymentFailed(Subscription $subscription, ?string $invoiceReference): void
    {
        $this->send($subscription, new SubscriptionPaymentFailedMail($subscription, $invoiceReference));
    }

    private function send(Subscription $subscription, Mailable $mail): void
    {
        try {
            $subscription->loadMissing('user');
            Mail::to($subscription->user->email)->send($mail);
        } catch (Throwable $exception) {
            // Webhook state is the source of truth. A temporary mail failure
            // must not roll back or repeatedly process the payment event.
            report($exception);
        }
    }
}
