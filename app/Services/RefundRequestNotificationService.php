<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Mail\RefundRequestDecisionMail;
use App\Mail\RefundRequestReceivedMail;
use App\Mail\RefundRequestSubmittedAdminMail;
use App\Models\Admin;
use App\Models\RefundRequest;
use App\Notifications\ClientNotification;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RefundRequestNotificationService
{
    public function submitted(RefundRequest $refundRequest): void
    {
        try {
            $refundRequest->loadMissing('user');
            Mail::to($refundRequest->user->email)->send(new RefundRequestReceivedMail($refundRequest));

            $billingEmails = Admin::query()
                ->whereIn('role', [AdminRole::SuperAdmin->value, AdminRole::Billing->value])
                ->pluck('email')
                ->all();

            if ($billingEmails !== []) {
                Mail::to($billingEmails)->send(new RefundRequestSubmittedAdminMail($refundRequest));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function decided(RefundRequest $refundRequest): void
    {
        try {
            $refundRequest->loadMissing(['user', 'invoice.order']);
            Mail::to($refundRequest->user->email)->send(new RefundRequestDecisionMail($refundRequest));

            $approved = $refundRequest->status->value === 'approved';
            $refundRequest->user->notify(new ClientNotification(
                title: $approved ? __('Refund request approved') : __('Refund request rejected'),
                message: __('Your refund request for invoice :number was :decision.', [
                    'number' => $refundRequest->invoice->invoice_number ?? '',
                    'decision' => $refundRequest->status->value,
                ]),
                url: route('account.invoices'),
                level: $approved ? 'success' : 'warning',
            ));

            app(WebhookDispatcher::class)->dispatch('refund.decided', [
                'request_number' => $refundRequest->request_number,
                'decision' => $refundRequest->status->value,
                'amount' => (float) $refundRequest->amount,
                'currency' => $refundRequest->currency,
                'invoice_number' => $refundRequest->invoice->invoice_number ?? null,
                'customer_email' => $refundRequest->user->email,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
