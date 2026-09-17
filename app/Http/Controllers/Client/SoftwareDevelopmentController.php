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
                'meta_title' => 'Web Development | ASR Tech',
                'meta_description' => 'Websites, customer portals, and custom web applications from ASR Tech. Discuss your requirements and the right scope for your business.',
                'canonical_url' => route('software-development'),
                'robots' => 'index,follow',
                'schema_json' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => 'Custom Software Development',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => config('asrtech.business.name'),
                    ],
                    'serviceType' => 'Custom software development',
                    'url' => route('software-development'),
                ],
            ],
        ]);
    }
}
