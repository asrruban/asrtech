@extends('mail.layout')

@section('badge', 'Product update')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $user->name }},</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
        Version {{ $release->version }} of {{ $release->product->name }} is now available for your active license.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#737980;">Version</p>
                <p style="margin:2px 0 12px;font-size:22px;font-weight:700;color:#2e3442;">
                    {{ $release->version }}
                </p>
                <p style="margin:0;font-size:13px;color:#737980;">
                    {{ $release->title ?: 'Product update' }} · {{ $release->released_at->format('d M Y') }}
                </p>
            </td>
        </tr>
    </table>
    @if ($release->release_notes)
        <p style="margin:20px 0 8px;font-size:13px;font-weight:700;color:#2e3442;">What changed</p>
        <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#5b6472;">
            {!! nl2br(e($release->release_notes)) !!}
        </p>
    @endif
    <p style="margin:0;font-size:13px;line-height:1.7;">
        <a href="{{ route('account.product', $license) }}" style="color:#357e37;font-weight:700;">
            View release and download
        </a>
    </p>
@endsection
