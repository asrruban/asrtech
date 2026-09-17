<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessPageController extends Controller
{
    public function services(): Response
    {
        return Inertia::render('Client/Services/Index', [
            'seo' => $this->seo('Services', 'Web and mobile development, WHMCS solutions, WordPress plugins, and technical maintenance from ASR Tech.', '/services'),
        ]);
    }

    public function service(string $service): Response
    {
        $services = config('asrtech.services', []);
        abort_unless(array_key_exists($service, $services), 404);

        return Inertia::render('Client/Services/Show', [
            'serviceSlug' => $service,
            'seo' => [
                ...$this->seo($services[$service], $services[$service].' for your business. Discuss your requirements with ASR Tech.', '/services/'.$service),
                'schema_json' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $services[$service],
                    'provider' => ['@type' => 'Organization', 'name' => config('asrtech.business.name')],
                    'url' => config('asrtech.business.website').'/services/'.$service,
                ],
            ],
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Client/About', [
            'seo' => $this->seo('About ASR Tech', 'Meet ASR Tech and Al Amin, Owner and Founder. Development and technical services based in Purbadhala, Netrokona, Bangladesh.', '/about'),
        ]);
    }

    public function contact(Request $request): Response
    {
        return Inertia::render('Client/Contact', [
            'selectedService' => array_key_exists($request->string('service')->toString(), config('asrtech.services', []))
                ? $request->string('service')->toString() : '',
            'inquiryReceived' => $request->session()->get('inquiry_received', false),
            'seo' => $this->seo('Discuss your project', 'Contact ASR Tech about development, WHMCS, WordPress, server maintenance, or technical support.', '/contact'),
        ]);
    }

    /** @return array<string, mixed> */
    private function seo(string $title, string $description, string $path): array
    {
        return [
            'meta_title' => $title.' | ASR Tech',
            'meta_description' => $description,
            'canonical_url' => config('asrtech.business.website').$path,
            'robots' => 'index,follow',
        ];
    }
}
