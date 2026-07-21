@extends('mail.layout')

@php
    $order = $invoice->order;
    $total = number_format($order->totalAmount(), 2);
    $productNames = $order->items->isNotEmpty()
        ? $order->items->pluck('product_name')->join(', ')
        : $order->product->name;
@endphp

@section('badge', 'Invoice')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $order->user->name }},</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
        @if ($invoice->status->value === 'paid')
            Thank you for your payment. Your invoice is attached for your records.
        @else
            Your invoice is ready. Please find the details below and the PDF attached.
        @endif
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#737980;">Invoice</p>
                <p style="margin:2px 0 12px;font-size:15px;font-weight:700;color:#2e3442;">{{ $invoice->invoice_number }}</p>
                <p style="margin:0;font-size:13px;color:#737980;">{{ $productNames }}</p>
                <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#2e3442;">{{ $order->currency }} {{ $total }}</p>
                @if ($invoice->due_at)
                    <p style="margin:10px 0 0;font-size:13px;color:#8a6116;">Due {{ $invoice->due_at->format('d M Y') }}</p>
                @endif
            </td>
        </tr>
    </table>
    <p style="margin:24px 0 0;font-size:12px;line-height:1.7;color:#8b93a0;">
        Questions? Reply to this email or contact {{ config('asrtech.support_email') }}.
    </p>
@endsection
