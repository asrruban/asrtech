<?php

use App\Http\Middleware\AuditAdminMutation;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\StorefrontMaintenance;
use App\Http\Middleware\TrackAffiliateReferral;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.audit' => AuditAdminMutation::class,
            'admin.permission' => EnsureAdminPermission::class,
            'api.token' => AuthenticateApiToken::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Gateway webhooks and license checks come from servers, not
        // browser sessions.
        $middleware->validateCsrfTokens(except: [
            'gateways/callback/*',
            'api/license/verify',
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin') || $request->is('admin/*')
                ? route('admin.login')
                : route('login'),
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            StorefrontMaintenance::class,
            TrackAffiliateReferral::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
