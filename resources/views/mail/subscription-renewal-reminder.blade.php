@extends('mail.layout')

@section('badge', 'Upcoming renewal')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $subscription->user->name }},</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
        Your {{ $subscription->product->name }} subscription will renew on
        {{ $subscription->current_period_end?->format('d M Y') }}.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#737980;">Recurring charge</p>
                <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#2e3442;">
                    {{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}
                </p>
            </td>
        </tr>
    </table>
    <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#5b6472;">
        Review your subscription and payment method in the client area.
    </p>
@endsection
