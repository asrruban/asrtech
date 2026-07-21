<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * WHMCS-style maintenance mode: the storefront shows a maintenance
 * page while admins (and the admin panel, gateway callbacks, and the
 * license API) keep working.
 */
class StorefrontMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('asrtech.maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'gateways/*', 'api/*', 'impersonation/*', 'up')) {
            return $next($request);
        }

        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        return Inertia::render('Client/Maintenance', [
            'message' => config('asrtech.maintenance_message'),
        ])->toResponse($request)->setStatusCode(503);
    }
}
