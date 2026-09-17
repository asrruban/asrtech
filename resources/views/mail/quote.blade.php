@extends('mail.layout')

@section('badge', 'Quote')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $quote->user->name }},</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
        We prepared quote <strong>{{ $quote->quote_number }}</strong> for you.
        @if ($quote->valid_until)
            It is valid until {{ $quote->valid_until->toFormattedDateString() }}.
        @endif
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e9ef;border-radius:10px;">
        @foreach ($quote->items as $item)
            <tr>
                <td style="padding:12px 20px;border-bottom:1px solid #eef1f5;font-size:14px;color:#2e3442;">
                    {{ $item->product_name }}@if ($item->quantity > 1) × {{ $item->quantity }}@endif
                </td>
                <td style="padding:12px 20px;border-bottom:1px solid #eef1f5;font-size:14px;color:#2e3442;text-align:right;">
                    {{ $quote->currency }} {{ number_format((float) $item->line_total, 2) }}
                </td>
            </tr>
        @endforeach
        @if ((float) $quote->tax_amount > 0)
            <tr>
                <td style="padding:12px 20px;font-size:13px;color:#737980;">Tax</td>
                <td style="padding:12px 20px;font-size:13px;color:#737980;text-align:right;">
                    {{ $quote->currency }} {{ number_format((float) $quote->tax_amount, 2) }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:14px 20px;font-size:15px;font-weight:700;color:#2e3442;">Total</td>
            <td style="padding:14px 20px;font-size:18px;font-weight:700;color:#357e37;text-align:right;">
                {{ $quote->currency }} {{ number_format((float) $quote->total, 2) }}
            </td>
        </tr>
    </table>
    <p style="margin:24px 0 0;font-size:13px;line-height:1.7;">
        <a href="{{ route('account.quotes.show', $quote) }}" style="color:#357e37;font-weight:700;">
            Review and accept this quote
        </a>
    </p>
@endsection
