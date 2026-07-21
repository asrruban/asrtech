<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\RefundRequestStatus;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundRequestService
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly RefundRequestNotificationService $notifications,
    ) {}

    /** @return array{can_request: bool, reason: string|null, refundable_amount: string, window_days: int, deadline: string|null} */
    public function eligibility(Invoice $invoice, User $user): array
    {
        $invoice->loadMissing('order');
        $windowDays = max(1, min(365, (int) config('asrtech.refunds.request_window_days', 30)));
        $deadline = $invoice->order->paid_at?->copy()->addDays($windowDays);
        $reason = match (true) {
            $invoice->order->user_id !== $user->id => $this->message('This invoice does not belong to your account.'),
            ! in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyRefunded], true) => $this->message('Only paid invoices can have a refund requested.'),
            $invoice->order->refundableAmount() <= 0 => $this->message('This invoice has no refundable balance.'),
            $invoice->order->paid_at === null => $this->message('The payment date is unavailable. Please contact support.'),
            $deadline?->isPast() => $this->message('The :days-day refund request window ended on :date.', [
                'days' => $windowDays,
                'date' => $deadline->format('d M Y'),
            ]),
            $invoice->refundRequests()->where('status', RefundRequestStatus::Pending)->exists() => $this->message('A refund request is already awaiting review.'),
            default => null,
        };

        return [
            'can_request' => $reason === null,
            'reason' => $reason,
            'refundable_amount' => number_format($invoice->order->refundableAmount(), 2, '.', ''),
            'window_days' => $windowDays,
            'deadline' => $deadline?->toIso8601String(),
        ];
    }

    public function submit(Invoice $invoice, User $user, float $amount, string $reason, string $idempotencyKey): RefundRequest
    {
        $amount = round($amount, 2);

        $refundRequest = DB::transaction(function () use ($invoice, $user, $amount, $reason, $idempotencyKey): RefundRequest {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $existing = RefundRequest::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                if ($existing->invoice_id !== $locked->id || $existing->user_id !== $user->id || round((float) $existing->amount, 2) !== $amount) {
                    throw ValidationException::withMessages(['refund_request' => __('This request key was already used with different details.')]);
                }

                return $existing;
            }

            $eligibility = $this->eligibility($locked, $user);
            if (! $eligibility['can_request']) {
                throw ValidationException::withMessages(['refund_request' => $eligibility['reason']]);
            }

            $remaining = (float) $eligibility['refundable_amount'];
            if ($amount <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages(['amount' => __('Enter an amount between 0.01 and :remaining.', ['remaining' => number_format($remaining, 2)])]);
            }

            return RefundRequest::query()->create([
                'invoice_id' => $locked->id,
                'user_id' => $user->id,
                'request_number' => $this->generateNumber(),
                'idempotency_key' => $idempotencyKey,
                'currency' => $locked->order->currency,
                'amount' => $amount,
                'status' => RefundRequestStatus::Pending,
                'reason' => trim($reason),
                'submitted_at' => now(),
            ]);
        });

        if ($refundRequest->wasRecentlyCreated) {
            $this->notifications->submitted($refundRequest);
        }

        return $refundRequest;
    }

    public function cancel(RefundRequest $refundRequest, User $user): RefundRequest
    {
        $cancelled = DB::transaction(function () use ($refundRequest, $user): RefundRequest {
            $locked = RefundRequest::query()->lockForUpdate()->findOrFail($refundRequest->id);
            abort_unless($locked->user_id === $user->id, 404);

            if ($locked->status !== RefundRequestStatus::Pending) {
                throw ValidationException::withMessages(['refund_request' => __('Only pending refund requests can be cancelled.')]);
            }

            $locked->update(['status' => RefundRequestStatus::Cancelled, 'decided_at' => now()]);

            return $locked->refresh();
        });

        $this->notifications->decided($cancelled);

        return $cancelled;
    }

    public function approve(RefundRequest $refundRequest, Admin $admin, bool $recordOnly, bool $revokeAccess, ?string $note): RefundRequest
    {
        $refundRequest->refresh();
        if ($refundRequest->status !== RefundRequestStatus::Pending) {
            throw ValidationException::withMessages(['refund_request' => __('This refund request has already been decided.')]);
        }

        $refundRequest->loadMissing('invoice.order');
        $refund = $this->refunds->issue(
            $refundRequest->invoice,
            $admin,
            (float) $refundRequest->amount,
            __('Customer request :number: :reason', ['number' => $refundRequest->request_number, 'reason' => $refundRequest->reason]),
            $refundRequest->idempotency_key,
            $recordOnly,
            $revokeAccess,
        );

        $approved = DB::transaction(function () use ($refundRequest, $refund, $admin, $note): RefundRequest {
            $locked = RefundRequest::query()->lockForUpdate()->findOrFail($refundRequest->id);
            if ($locked->status === RefundRequestStatus::Pending) {
                $locked->update([
                    'refund_id' => $refund->id,
                    'decided_by' => $admin->id,
                    'status' => RefundRequestStatus::Approved,
                    'admin_note' => filled($note) ? trim((string) $note) : __('Approved and sent to the original payment method.'),
                    'decided_at' => now(),
                ]);
            }

            return $locked->refresh();
        });

        $this->notifications->decided($approved);

        return $approved;
    }

    public function reject(RefundRequest $refundRequest, Admin $admin, string $note): RefundRequest
    {
        $rejected = DB::transaction(function () use ($refundRequest, $admin, $note): RefundRequest {
            $locked = RefundRequest::query()->lockForUpdate()->findOrFail($refundRequest->id);
            if ($locked->status !== RefundRequestStatus::Pending) {
                throw ValidationException::withMessages(['refund_request' => __('This refund request has already been decided.')]);
            }

            $locked->update([
                'decided_by' => $admin->id,
                'status' => RefundRequestStatus::Rejected,
                'admin_note' => trim($note),
                'decided_at' => now(),
            ]);

            return $locked->refresh();
        });

        $this->notifications->decided($rejected);

        return $rejected;
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $sequence = RefundRequest::query()->where('request_number', 'like', "RR-{$year}-%")->count() + 1;

        do {
            $number = sprintf('RR-%s-%05d', $year, $sequence++);
        } while (RefundRequest::query()->where('request_number', $number)->exists());

        return $number;
    }

    /** @param array<string, int|string> $replace */
    private function message(string $key, array $replace = []): string
    {
        $message = __($key, $replace);

        return is_string($message) ? $message : $key;
    }
}
