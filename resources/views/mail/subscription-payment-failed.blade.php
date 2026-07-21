@extends('mail.layout')

@section('badge', 'Payment failed')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $subscription->user->name }},</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
        We could not renew your {{ $subscription->product->name }} subscription. Update your payment method to avoid losing access.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#9a3412;">Amount due</p>
                <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#7c2d12;">
                    {{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}
                </p>
            </td>
        </tr>
    </table>
    <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#5b6472;">
        Sign in to the client area and choose Update payment method.
    </p>
@endsection
