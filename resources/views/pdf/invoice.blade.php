<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2e3442; padding: 40px; }
        .header { width: 100%; margin-bottom: 30px; }
        .header td { vertical-align: top; }
        .company { font-size: 20px; font-weight: bold; }
        .muted { color: #737980; }
        .invoice-title { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #737980; text-align: right; }
        .invoice-number { font-size: 16px; font-weight: bold; text-align: right; margin-top: 4px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background: #d1f2d3; color: #2c6e2f; }
        .status-partially_refunded { background: #dbeafe; color: #1d4ed8; }
        .status-issued { background: #fdeeca; color: #8a6116; }
        .status-void { background: #fadbd8; color: #943126; }
        .meta { width: 100%; margin: 24px 0; border-top: 1px solid #d7dadb; padding-top: 16px; }
        .meta td { vertical-align: top; width: 33%; }
        .meta .label { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #737980; margin-bottom: 4px; }
        .meta .value { font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.items th { text-align: left; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #737980; border-bottom: 1px solid #d7dadb; padding: 8px 0; }
        table.items th.right, table.items td.right { text-align: right; }
        table.items td { padding: 12px 0; border-bottom: 1px solid #eef0f2; }
        .item-name { font-weight: bold; }
        .item-sub { font-size: 10px; color: #737980; margin-top: 3px; font-family: DejaVu Sans Mono, monospace; }
        .total-row td { border-bottom: none; padding-top: 16px; }
        .total-label { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #737980; text-align: right; }
        .total-value { font-size: 18px; font-weight: bold; text-align: right; }
        .payto { margin-top: 28px; border-top: 1px solid #d7dadb; padding-top: 14px; }
        .footer { margin-top: 36px; text-align: center; font-size: 10px; color: #8b93a0; }
    </style>
</head>
<body>
    @php
        $order = $invoice->order;
        $total = $order->totalAmount();
        $money = fn ($amount) => $order->currency.' '.number_format((float) $amount, 2);
        $cycle = ucwords(str_replace('_', ' ', $order->billing_cycle->value ?? (string) $order->billing_cycle));
        $items = $order->items;
    @endphp

    <table class="header">
        <tr>
            <td>
                <div class="company">{{ config('asrtech.company_name', config('app.name')) }}</div>
                @if (config('asrtech.address'))
                    <div class="muted" style="margin-top: 6px; white-space: pre-line;">{{ config('asrtech.address') }}</div>
                @endif
                @if (config('asrtech.support_email'))
                    <div class="muted" style="margin-top: 4px;">{{ config('asrtech.support_email') }}</div>
                @endif
            </td>
            <td>
                <div class="invoice-title">Invoice</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="text-align: right; margin-top: 8px;">
                    <span class="status status-{{ $invoice->status->value }}">{{ $invoice->status->value }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Billed to</div>
                <div class="value">{{ $order->user->name }}</div>
                <div class="muted">{{ $order->user->email }}</div>
            </td>
            <td>
                <div class="label">Issued</div>
                <div class="value">{{ $invoice->issued_at->format('d M Y') }}</div>
            </td>
            <td>
                <div class="label">{{ $invoice->due_at ? 'Due' : 'Order' }}</div>
                <div class="value">{{ $invoice->due_at ? $invoice->due_at->format('d M Y') : $order->order_number }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Billing</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->product_name }}</div>
                        <div class="item-sub">Order {{ $order->order_number }}</div>
                        @if ($license = $order->licenses->firstWhere('product_id', $item->product_id))
                            <div class="item-sub">License: {{ $license->license_key }}</div>
                        @endif
                    </td>
                    <td>{{ $item->price_name ?: ucwords(str_replace('_', ' ', $item->billing_cycle->value)) }}</td>
                    <td class="right"><strong>{{ $money($item->amount) }}</strong></td>
                </tr>
                @if ((float) $item->setup_fee > 0)
                    <tr>
                        <td>{{ $item->product_name }} setup fee</td>
                        <td></td>
                        <td class="right"><strong>{{ $money($item->setup_fee) }}</strong></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td>
                        <div class="item-name">{{ $order->product->name }}</div>
                        <div class="item-sub">Order {{ $order->order_number }}</div>
                        @if ($order->license)
                            <div class="item-sub">License: {{ $order->license->license_key }}</div>
                        @endif
                    </td>
                    <td>{{ $order->productPrice->name ?? $cycle }}</td>
                    <td class="right"><strong>{{ $money($order->amount) }}</strong></td>
                </tr>
                @if ((float) $order->setup_fee > 0)
                    <tr>
                        <td>Setup fee</td>
                        <td></td>
                        <td class="right"><strong>{{ $money($order->setup_fee) }}</strong></td>
                    </tr>
                @endif
            @endforelse
            @if ((float) $order->discount_amount > 0)
                <tr>
                    <td>Promotion {{ $order->promotion_code ? '('.$order->promotion_code.')' : '' }}</td>
                    <td></td>
                    <td class="right"><strong>-{{ $money($order->discount_amount) }}</strong></td>
                </tr>
            @endif
            @if ((float) $order->tax_amount > 0)
                <tr>
                    <td>{{ $order->tax_name ?: 'Tax' }}{{ $order->tax_rate !== null ? ' ('.number_format((float) $order->tax_rate, 4).'%'.')' : '' }}</td>
                    <td></td>
                    <td class="right"><strong>{{ $money($order->tax_amount) }}</strong></td>
                </tr>
            @endif
            <tr class="total-row">
                <td></td>
                <td class="total-label">Total</td>
                <td class="total-value">{{ $money($total) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($payTo)
        <div class="payto">
            <div class="meta" style="border: none; margin: 0; padding: 0;">
                <div class="label" style="font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #737980; margin-bottom: 4px;">Pay to</div>
                <div style="white-space: pre-line;">{{ $payTo }}</div>
            </div>
        </div>
    @endif

    <div class="footer">
        {{ $footerNote ?: 'Thank you for your business.' }}
        Payment method: {{ ucwords(str_replace('_', ' ', $order->payment_method ?? 'online')) }}.
    </div>
</body>
</html>
