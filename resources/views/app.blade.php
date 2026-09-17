<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Match the persisted appearance before styles load to avoid a theme flash. --}}
        <script>
            (function() {
                const valid = (value) => ['light', 'dark', 'system'].includes(value);
                const serverAppearance = @js($appearance ?? 'system');
                let appearance = valid(serverAppearance) ? serverAppearance : 'system';

                try {
                    const stored = window.localStorage.getItem('appearance');
                    if (valid(stored)) {
                        appearance = stored;
                    }
                } catch {
                    // Cookie or system appearance works when local storage is blocked.
                }

                const dark = appearance === 'dark' ||
                    (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
                color-scheme: light;
            }

            html.dark {
                background-color: #102323;
                color-scheme: dark;
            }
        </style>

        @if (config('asrtech.favicon_url'))
            <link rel="icon" href="{{ config('asrtech.favicon_url') }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- PWA: installable client portal --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#063023">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="ASRTech">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

        {{-- Search engine ownership verification (Global SEO settings) --}}
        @foreach ([
            'google-site-verification' => config('asrtech.seo.verification.google'),
            'msvalidate.01' => config('asrtech.seo.verification.bing'),
            'yandex-verification' => config('asrtech.seo.verification.yandex'),
            'baidu-site-verification' => config('asrtech.seo.verification.baidu'),
            'p:domain_verify' => config('asrtech.seo.verification.pinterest'),
        ] as $verificationName => $verificationCode)
            @if (filled($verificationCode))
                <meta name="{{ $verificationName }}" content="{{ $verificationCode }}">
            @endif
        @endforeach

        @unless (request()->is('admin*'))
            @if (filled(config('asrtech.analytics.gtm')))
                {{-- Google Tag Manager --}}
                <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','{{ config('asrtech.analytics.gtm') }}');</script>
            @endif
            @if (filled(config('asrtech.analytics.ga4')))
                {{-- Google Analytics 4 --}}
                <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('asrtech.analytics.ga4') }}"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', '{{ config('asrtech.analytics.ga4') }}');
                </script>
            @endif
            @if (filled(config('asrtech.analytics.meta_pixel')))
                {{-- Meta Pixel --}}
                <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                document,'script','https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ config('asrtech.analytics.meta_pixel') }}');
                fbq('track', 'PageView');</script>
                <noscript><img height="1" width="1" style="display:none" alt=""
                    src="https://www.facebook.com/tr?id={{ config('asrtech.analytics.meta_pixel') }}&ev=PageView&noscript=1"></noscript>
            @endif
        @endunless

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            @include('partials.seo')
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        @unless (request()->is('admin*'))
            @if (filled(config('asrtech.analytics.gtm')))
                {{-- Google Tag Manager (noscript) --}}
                <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('asrtech.analytics.gtm') }}"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            @endif
        @endunless
        <x-inertia::app />
    </body>
</html>
