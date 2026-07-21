<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The endpoint product installations call to validate their license,
 * equivalent to the WHMCS licensing addon verify call.
 */
class LicenseVerifyController extends Controller
{
    public function __construct(private readonly LicenseVerificationService $verification) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'string', 'max:45'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->verification->verify(
            $data['license_key'],
            $data['domain'] ?? null,
            $data['ip'] ?? null,
            $data['path'] ?? null,
        ));
    }
}
