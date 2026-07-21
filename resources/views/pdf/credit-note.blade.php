<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 40px; font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #273142; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .company { font-size: 20px; font-weight: bold; }
        .title { text-align: right; color: #667085; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; }
        .number { margin-top: 5px; text-align: right; font-size: 16px; font-weight: bold; }
        .meta { margin-top: 30px; border-top: 1px solid #d8dee8; }
        .meta td { width: 33%; padding-top: 16px; vertical-align: top; }
        .label { color: #667085; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .value { margin-top: 4px; font-weight: bold; }
        .items { margin-top: 30px; }
        .items th { padding: 8px 0; border-bottom: 1px solid #d8dee8; color: #667085; font-size: 9px; letter-spacing: 1px; text-align: left; text-transform: uppercase; }
        .items td { padding: 12px 0; border-bottom: 1px solid #edf0f4; }
        .right { text-align: right !important; }
        .total td { padding-top: 16px; border: 0; font-size: 16px; font-weight: bold; }
        .reason { margin-top: 28px; padding: 14px; background: #f5f7fa; }
        .footer { margin-top: 40px; text-align: center; color: #7d8796; font-size: 10px; }
    </style>
</head>
<body>
@php
    $invoice = $creditNote->invoice;
    $order = $invoice->order;
    $refund = $creditNote->refund;
    $money = fn ($amount) => $creditNote->currency.' '.number_format((float) $amount, 2);
@endphp

<table class="header">
    <tr>
        <td>
            <div class="company">{{ config('asrtech.company_name', config('app.name')) }}</div>
            @if ($payTo)<div style="margin-top: 6px; color: #667085; white-space: pre-line;">{{ $payTo }}</div>@endif
        </td>
        <td>
            <div class="title">Credit Note</div>
            <div class="number">{{ $creditNote->credit_note_number }}</div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><div class="label">Credited to</div><div class="value">{{ $order->user->name }}</div><div>{{ $order->user->email }}</div></td>
        <td><div class="label">Issued</div><div class="value">{{ $creditNote->issued_at->format('d M Y') }}</div></td>
        <td><div class="label">Original invoice</div><div class="value">{{ $invoice->invoice_number }}</div><div>{{ $order->order_number }}</div></td>
    </tr>
</table>

<table class="items">
    <thead><tr><th>Description</th><th class="right">Credit</th></tr></thead>
    <tbody>
        <tr><td>{{ $order->product->name }} refund</td><td class="right">{{ $money($creditNote->net_amount) }}</td></tr>
        @if ((float) $creditNote->tax_amount > 0)
            <tr><td>{{ $creditNote->tax_name ?: 'Tax adjustment' }}</td><td class="right">{{ $money($creditNote->tax_amount) }}</td></tr>
        @endif
        <tr class="total"><td class="right">Total credit</td><td class="right">{{ $money($creditNote->total_amount) }}</td></tr>
    </tbody>
</table>

<div class="reason"><strong>Reason:</strong> {{ $creditNote->reason }}</div>
<div class="footer">Refund {{ $refund->refund_number }} · {{ ucfirst($refund->status->value) }} via {{ ucwords(str_replace('_', ' ', $refund->gateway)) }}</div>
</body>
</html>
