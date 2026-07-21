@extends('mail.layout')
@section('badge', 'Refund request update')
@section('content')
<p>Hi {{ $refundRequest->user->name }},</p>
<p>Your refund request <strong>{{ $refundRequest->request_number }}</strong> has been <strong>{{ $refundRequest->status->value }}</strong>.</p>
@if($refundRequest->admin_note)<p>{{ $refundRequest->admin_note }}</p>@endif
<p><a href="{{ route('account.invoice', $refundRequest->invoice_id) }}">View invoice and request</a></p>
@endsection
