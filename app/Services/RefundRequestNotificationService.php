<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Mail\RefundRequestDecisionMail;
use App\Mail\RefundRequestReceivedMail;
use App\Mail\RefundRequestSubmittedAdminMail;
use App\Models\Admin;
use App\Models\RefundRequest;
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
            $refundRequest->loadMissing('user');
            Mail::to($refundRequest->user->email)->send(new RefundRequestDecisionMail($refundRequest));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
