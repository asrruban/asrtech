@extends('mail.layout')

@section('badge', 'Verify email')

@section('content')
    <p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{ $user->name }},</p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#5b6472;">
        Use this code to verify your email address. It expires in {{ $ttlMinutes }} minutes.
    </p>
    <p style="margin:0 0 24px;text-align:center;">
        <span style="display:inline-block;background:#eff9ef;border:1px solid #4fb250;border-radius:10px;padding:14px 28px;font-size:30px;font-weight:700;letter-spacing:8px;color:#357e37;">{{ $code }}</span>
    </p>
    <p style="margin:0;font-size:13px;line-height:1.7;color:#8b93a0;">
        If you did not create an account, you can safely ignore this email.
    </p>
@endsection
