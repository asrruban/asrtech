<?php

return [
    // Public business identity supplied by the business owner.
    'business' => [
        'name' => 'ASR Tech',
        'owner' => 'Al Amin',
        'address' => '2nd Floor Johor Uddin Market, Purbadhala Bazar, Purbadhala, Netrokona 2410, Bangladesh',
        'website' => 'https://www.asrtech.bd',
        'facebook' => 'https://web.facebook.com/asrtechofficial',
    ],
    'services' => [
        'web-development' => 'Web development',
        'mobile-app-development' => 'Mobile app development',
        'whmcs-customisation' => 'WHMCS customisation and modification',
        'whmcs-modules-templates' => 'WHMCS modules and templates',
        'wordpress-plugins' => 'WordPress plugins',
        'wordpress-whmcs-management' => 'WordPress and WHMCS management',
        'server-management' => 'Server management and maintenance',
        'technical-support' => 'Troubleshooting and technical support',
    ],
    'company_name' => env('APP_NAME', 'ASR Tech'),
    'tagline' => 'Development, software, and technical support for your business.',
    'support_email' => null,
    'support' => [
        // Shared secret for the inbound-email webhook (ticket email piping).
        // Point your provider's inbound route to POST /api/inbound-email
        // with fields: token, from, subject, text.
        'inbound_token' => env('SUPPORT_INBOUND_TOKEN'),
    ],
    'phone' => null,
    'address' => null,
    'logo_url' => null,
    'currency' => 'USD',
    'subscriptions' => [
        'reminders_enabled' => false,
        'reminder_days' => 7,
        'grace_days' => 3,
    ],
    'dunning' => [
        // Escalating retry reminders sent after a renewal payment fails.
        'enabled' => true,
        // Days after the failed payment at which reminder emails are sent.
        'reminder_days' => [1, 3, 7],
    ],
    'refunds' => [
        'request_window_days' => (int) env('REFUND_REQUEST_WINDOW_DAYS', 30),
    ],
    'affiliates' => [
        'default_commission_rate' => (float) env('AFFILIATE_COMMISSION_RATE', 10),
        'cookie_days' => (int) env('AFFILIATE_COOKIE_DAYS', 30),
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
