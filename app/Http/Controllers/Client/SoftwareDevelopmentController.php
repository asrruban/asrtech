<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareDevelopmentController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Client/SoftwareDevelopment', [
            'seo' => [
                'meta_title' => 'Custom Software Development | ASRTech',
                'meta_description' => 'Custom Laravel, Vue, WHMCS, API integration, automation, and hosting software development from ASRTech.',
                'canonical_url' => route('software-development'),
                'robots' => 'index,follow',
                'schema_json' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => 'Custom Software Development',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => config('asrtech.company_name', 'ASRTech'),
                    ],
                    'serviceType' => 'Custom software development',
                    'url' => route('software-development'),
                ],
            ],
        ]);
    }
}
