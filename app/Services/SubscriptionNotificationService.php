<?php

namespace App\Services;

use App\Mail\SubscriptionCancellationScheduledMail;
use App\Mail\SubscriptionPaymentFailedMail;
use App\Mail\SubscriptionRenewedMail;
use App\Models\Order;
use App\Models\Subscription;
use App\Notifications\ClientNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionNotificationService
{
    public function cancellationScheduled(Subscription $subscription): void
    {
        $this->send($subscription, new SubscriptionCancellationScheduledMail($subscription));
        $this->notify($subscription, new ClientNotification(
            title: __('Subscription cancellation scheduled'),
            message: __('Your :product subscription will end at the current period.', [
                'product' => $subscription->product->name ?? '',
            ]),
            url: route('account.subscriptions'),
            level: 'warning',
        ));
    }

    public function renewed(Subscription $subscription, Order $renewalOrder): void
    {
        $this->send($subscription, new SubscriptionRenewedMail($subscription, $renewalOrder));
        $this->notify($subscription, new ClientNotification(
            title: __('Subscription renewed'),
            message: __('Your :product subscription renewed successfully.', [
                'product' => $subscription->product->name ?? '',
            ]),
            url: route('account.subscriptions'),
            level: 'success',
        ));
    }

    public function paymentFailed(Subscription $subscription, ?string $invoiceReference): void
    {
        $this->send($subscription, new SubscriptionPaymentFailedMail($subscription, $invoiceReference));
        $this->notify($subscription, new ClientNotification(
            title: __('Payment failed'),
            message: __('We could not renew your :product subscription. Please update your payment method.', [
                'product' => $subscription->product->name ?? '',
            ]),
            url: route('account.subscriptions'),
            level: 'danger',
        ));
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

    private function notify(Subscription $subscription, ClientNotification $notification): void
    {
        try {
            $subscription->loadMissing('user');
            $subscription->user->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
