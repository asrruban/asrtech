<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ASR Tech inquiry</title></head>
<body style="margin:0;background:#f4f8f7;color:#182624;font-family:Arial,sans-serif;line-height:1.7;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:32px 16px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;margin:auto;background:#fff;border:1px solid #dce7e3;border-radius:16px;"><tr><td style="padding:32px;">
<p style="font-size:22px;font-weight:bold;margin:0 0 24px;">ASR <span style="color:#087f75;">Tech</span></p>
@if ($isAcknowledgement)
<h1 style="font-size:24px;line-height:1.3;">We have received your inquiry.</h1>
<p>Thank you for contacting ASR Tech. Your inquiry has been saved for review. We will use the contact details you provided to discuss the next steps.</p>
<p>Reference: #{{ $inquiry->id }}</p>
<p>If you did not submit an inquiry, you can ignore this message.</p>
@else
<h1 style="font-size:24px;line-height:1.3;">A new inquiry is ready for review.</h1>
<p><strong>Reference:</strong> #{{ $inquiry->id }}<br><strong>Name:</strong> {{ $inquiry->name }}<br><strong>Email:</strong> {{ $inquiry->email }}<br><strong>Service:</strong> {{ config('asrtech.services.'.$inquiry->service, $inquiry->service) }}</p>
<p style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $inquiry->message }}</p>
<p><a href="{{ $inboxUrl }}" style="color:#087f75;font-weight:bold;">Open the inquiry inbox</a></p>
@endif
<p style="margin-top:28px;font-size:12px;color:#52615d;">{{ config('asrtech.business.address') }}</p>
</td></tr></table></td></tr></table>
</body></html>
