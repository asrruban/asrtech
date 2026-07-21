<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#eef1f5;font-family:'Segoe UI',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(20,30,50,.08);">
                    <tr>
                        <td>
                            @if (filled(config('asrtech.email.header_html')))
                                {!! config('asrtech.email.header_html') !!}
                            @else
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#26364d 0%,#1a2333 100%);background-color:#26364d;">
                                    <tr>
                                        <td style="padding:28px 36px;">
                                            @php
                                                $mailLogo = (string) (config('asrtech.logo_dark_url') ?: config('asrtech.logo_url'));
                                                if ($mailLogo !== '' && ! \Illuminate\Support\Str::startsWith($mailLogo, ['http://', 'https://'])) {
                                                    $mailLogo = rtrim((string) config('app.url'), '/').$mailLogo;
                                                }
                                            @endphp
                                            @if ($mailLogo !== '')
                                                <img src="{{ $mailLogo }}" alt="{{ config('asrtech.company_name', config('app.name')) }}" style="max-height:40px;width:auto;">
                                            @else
                                                <span style="color:#ffffff;font-size:19px;font-weight:700;letter-spacing:.3px;">{{ config('asrtech.company_name', config('app.name')) }}</span>
                                            @endif
                                        </td>
                                        <td align="right" style="padding:28px 36px;">
                                            <span style="color:#84d780;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">@yield('badge')</span>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 36px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td>
                            @if (filled(config('asrtech.email.footer_html')))
                                {!! config('asrtech.email.footer_html') !!}
                            @else
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-top:1px solid #e6e9ee;">
                                    <tr>
                                        <td style="padding:22px 36px;">
                                            @if (config('asrtech.mail_signature'))
                                                <p style="margin:0 0 10px;font-size:12px;line-height:1.7;color:#5b6472;white-space:pre-line;">{{ config('asrtech.mail_signature') }}</p>
                                            @endif
                                            <p style="margin:0;font-size:11px;line-height:1.7;color:#8b93a0;">
                                                {{ config('asrtech.company_name', config('app.name')) }}
                                                @if (config('asrtech.support_email'))
                                                    · {{ config('asrtech.support_email') }}
                                                @endif
                                                <br>
                                                &copy; {{ now()->year }} {{ config('asrtech.company_name', config('app.name')) }}. All rights reserved.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
