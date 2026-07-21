@extends('mail.layout')
@section('badge', 'Billing review required')
@section('content')
<p>{{ $refundRequest->user->name }} submitted refund request <strong>{{ $refundRequest->request_number }}</strong> for {{ $refundRequest->currency }} {{ number_format((float) $refundRequest->amount, 2) }} against invoice {{ $refundRequest->invoice->invoice_number }}.</p>
<p><strong>Reason:</strong> {{ $refundRequest->reason }}</p>
<p><a href="{{ route('admin.refund-requests.show', $refundRequest) }}">Review request</a></p>
@endsection
