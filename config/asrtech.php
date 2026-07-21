<?php

return [
    'company_name' => env('APP_NAME', 'ASRTech'),
    'tagline' => 'WHMCS modules, templates, and professional web development.',
    'support_email' => null,
    'phone' => null,
    'address' => null,
    'logo_url' => null,
    'currency' => 'USD',
    'subscriptions' => [
        'reminders_enabled' => false,
        'reminder_days' => 7,
        'grace_days' => 3,
    ],
    'refunds' => [
        'request_window_days' => (int) env('REFUND_REQUEST_WINDOW_DAYS', 30),
    ],
    'social' => [
        'facebook' => null,
        'linkedin' => null,
        'github' => null,
    ],
    'seo' => [
        'title' => env('APP_NAME', 'ASRTech'),
        'description' => 'WHMCS modules, templates, licenses, and professional web development services.',
        'image' => null,
        'home' => [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'image' => null,
        ],
        'verification' => [
            'google' => null,
            'bing' => null,
            'yandex' => null,
            'baidu' => null,
            'pinterest' => null,
        ],
    ],
    'analytics' => [
        'ga4' => null,
        'gtm' => null,
        'meta_pixel' => null,
    ],
    'storage' => [
        'driver' => 'local',
        'paths' => [
            'branding' => 'branding',
            'tickets' => 'support/tickets',
            'products' => 'products',
        ],
    ],
];
