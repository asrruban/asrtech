@extends('mail.layout')
@section('badge', 'Refund request received')
@section('content')
<p>Hi {{ $refundRequest->user->name }},</p>
<p>We received refund request <strong>{{ $refundRequest->request_number }}</strong> for {{ $refundRequest->currency }} {{ number_format((float) $refundRequest->amount, 2) }}. Our billing team will review it.</p>
<p><a href="{{ route('account.invoice', $refundRequest->invoice_id) }}">Track your request</a></p>
@endsection
