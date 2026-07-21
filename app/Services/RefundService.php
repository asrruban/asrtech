<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\Admin;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Refund;
use App\Payments\GatewayRegistry;
use App\Payments\RefundableGateway;
use App\Payments\RefundResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private readonly GatewayRegistry $gateways) {}

    public function supportsAutomatic(Order $order): bool
    {
        return $this->gateways->find((string) $order->payment_method) instanceof RefundableGateway;
    }

    public function issue(
        Invoice $invoice,
        Admin $admin,
        float $amount,
        string $reason,
        string $idempotencyKey,
        bool $recordOnly = false,
        bool $revokeAccess = false,
    ): Refund {
        $amount = round($amount, 2);
        $reason = trim($reason);
        $gateway = $this->gateways->find((string) $invoice->order->payment_method);

        if (! $recordOnly && ! $gateway instanceof RefundableGateway) {
            throw ValidationException::withMessages([
                'record_only' => __('This payment method does not support automatic refunds. Choose record-only mode after refunding it externally.'),
            ]);
        }

        $refund = DB::transaction(function () use (
            $invoice,
            $admin,
            $amount,
            $reason,
            $idempotencyKey,
            $recordOnly,
            $revokeAccess,
        ): Refund {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $lockedInvoice->load('order');
            $existing = Refund::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                if ($existing->invoice_id !== $lockedInvoice->id || round((float) $existing->amount, 2) !== $amount) {
                    throw ValidationException::withMessages([
                        'refund' => __('This refund request key was already used with different details.'),
                    ]);
                }

                if (in_array($existing->status, [RefundStatus::Processing, RefundStatus::Succeeded], true)) {
                    return $existing;
                }

                $existing->update([
                    'status' => RefundStatus::Pending,
                    'failure_message' => null,
                ]);

                return $existing->refresh();
            }

            if (! in_array($lockedInvoice->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyRefunded], true)) {
                throw ValidationException::withMessages([
                    'invoice' => __('Only paid invoices with a refundable balance can be refunded.'),
                ]);
            }

            $remaining = $lockedInvoice->order->refundableAmount();
            if ($amount <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => __('Enter an amount between 0.01 and :remaining.', [
                        'remaining' => number_format($remaining, 2),
                    ]),
                ]);
            }

            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => __('A refund reason is required.')]);
            }

            return Refund::query()->create([
                'order_id' => $lockedInvoice->order_id,
                'invoice_id' => $lockedInvoice->id,
                'original_transaction_id' => $lockedInvoice->order->transactions()
                    ->where('type', 'payment')
                    ->latest('id')
                    ->value('id'),
                'admin_id' => $admin->id,
                'refund_number' => $this->generateRefundNumber(),
                'idempotency_key' => $idempotencyKey,
                'gateway' => (string) ($lockedInvoice->order->payment_method ?? 'manual'),
                'currency' => $lockedInvoice->order->currency,
                'amount' => $amount,
                'status' => RefundStatus::Pending,
                'reason' => $reason,
                'record_only' => $recordOnly,
                'revoke_access' => $revokeAccess,
            ]);
        });

        if (in_array($refund->status, [RefundStatus::Processing, RefundStatus::Succeeded], true)) {
            return $refund->load('creditNote');
        }

        $result = $recordOnly
            ? RefundResult::succeeded('manual_refund_'.$refund->idempotency_key)
            : $gateway->refund($refund->order, $amount, $idempotencyKey);

        if (! $result->accepted) {
            $refund->update([
                'status' => RefundStatus::Failed,
                'failure_message' => $result->message ?: 'The payment gateway rejected the refund.',
                'processed_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'refund' => $refund->failure_message,
            ]);
        }

        return DB::transaction(fn (): Refund => $this->finalize($refund, $result));
    }

    private function finalize(Refund $refund, RefundResult $result): Refund
    {
        $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);
        if (in_array($locked->status, [RefundStatus::Processing, RefundStatus::Succeeded], true)) {
            return $locked->load('creditNote');
        }

        $locked->load(['order', 'invoice']);
        $status = $result->status === RefundStatus::Succeeded->value
            ? RefundStatus::Succeeded
            : RefundStatus::Processing;
        $locked->update([
            'status' => $status,
            'gateway_reference' => $result->reference,
            'failure_message' => null,
            'processed_at' => now(),
        ]);

        $transaction = $locked->order->transactions()->create([
            'type' => 'refund',
            'gateway' => $locked->gateway,
            'reference' => $result->reference,
            'amount' => $locked->amount,
            'description' => "Refund {$locked->refund_number}: {$locked->reason}",
        ]);
        $locked->update(['transaction_id' => $transaction->id]);

        $this->createCreditNote($locked);

        $refunded = round((float) $locked->order->refunds()
            ->whereIn('status', RefundStatus::accepted())
            ->sum('amount'), 2);
        $fullyRefunded = $refunded >= $locked->order->totalAmount() - 0.009;
        $locked->invoice->update([
            'status' => $fullyRefunded ? InvoiceStatus::Refunded : InvoiceStatus::PartiallyRefunded,
        ]);
        $locked->order->update([
            'status' => $fullyRefunded ? OrderStatus::Refunded : OrderStatus::Paid,
        ]);

        if ($fullyRefunded && $locked->revoke_access) {
            $locked->order->licenses()->update(['status' => LicenseStatus::Terminated]);
        }

        return $locked->refresh()->load('creditNote');
    }

    private function createCreditNote(Refund $refund): CreditNote
    {
        $order = $refund->order;
        $creditedTax = (float) CreditNote::query()
            ->where('invoice_id', $refund->invoice_id)
            ->sum('tax_amount');
        $remainingTax = max(0, round((float) $order->tax_amount - $creditedTax, 2));
        $remainingRefund = round($order->totalAmount() - $order->refundedAmount() + (float) $refund->amount, 2);
        $isFinal = (float) $refund->amount >= $remainingRefund - 0.009;
        $taxAmount = $isFinal
            ? $remainingTax
            : min($remainingTax, round((float) $refund->amount * (float) $order->tax_amount / max(0.01, $order->totalAmount()), 2));
        $taxAmount = min((float) $refund->amount, $taxAmount);

        return $refund->creditNote()->create([
            'invoice_id' => $refund->invoice_id,
            'credit_note_number' => $this->generateCreditNoteNumber(),
            'currency' => $refund->currency,
            'net_amount' => round((float) $refund->amount - $taxAmount, 2),
            'tax_amount' => $taxAmount,
            'total_amount' => $refund->amount,
            'tax_name' => $order->tax_name,
            'tax_rate' => $order->tax_rate,
            'reason' => $refund->reason,
            'issued_at' => now(),
        ]);
    }

    private function generateRefundNumber(): string
    {
        $year = now()->format('Y');
        $sequence = Refund::query()->where('refund_number', 'like', "RF-{$year}-%")->count() + 1;

        do {
            $number = sprintf('RF-%s-%05d', $year, $sequence++);
        } while (Refund::query()->where('refund_number', $number)->exists());

        return $number;
    }

    private function generateCreditNoteNumber(): string
    {
        $year = now()->format('Y');
        $sequence = CreditNote::query()->where('credit_note_number', 'like', "CN-{$year}-%")->count() + 1;

        do {
            $number = sprintf('CN-%s-%05d', $year, $sequence++);
        } while (CreditNote::query()->where('credit_note_number', $number)->exists());

        return $number;
    }
}
