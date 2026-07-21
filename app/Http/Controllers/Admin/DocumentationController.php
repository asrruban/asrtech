<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Docs/Index', [
            'licenseApi' => [
                'endpoint' => route('license.verify'),
                'method' => 'POST',
                'rate_limit' => '60 requests per minute',
                'fields' => [
                    ['name' => 'license_key', 'required' => true, 'description' => 'The customer license key.'],
                    ['name' => 'domain', 'required' => false, 'description' => 'Installation hostname, without a URL scheme.'],
                    ['name' => 'ip', 'required' => false, 'description' => 'Public server IP address.'],
                    ['name' => 'path', 'required' => false, 'description' => 'Absolute installation directory.'],
                ],
                'statuses' => [
                    ['value' => 'active', 'meaning' => 'The installation may continue running.'],
                    ['value' => 'invalid', 'meaning' => 'The key or installation details do not match.'],
                    ['value' => 'suspended', 'meaning' => 'Access is temporarily disabled.'],
                    ['value' => 'expired', 'meaning' => 'The license expiry date has passed.'],
                    ['value' => 'terminated', 'meaning' => 'The license has been permanently terminated.'],
                ],
            ],
        ]);
    }
}
