<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\AdminAuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditAdminMutation
{
    public function __construct(private readonly AdminAuditService $audit) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');
        $response = $next($request);

        if (! $admin instanceof Admin
            || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            || $response->getStatusCode() >= 400) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? 'admin.request';
        $subject = collect($request->route()?->parameters() ?? [])
            ->first(fn (mixed $parameter): bool => $parameter instanceof Model);

        $this->audit->record(
            $admin,
            $routeName,
            Str::headline(str_replace('admin.', '', $routeName)),
            $subject instanceof Model ? $subject : null,
            $request->except(['_token']),
            $request,
        );

        return $response;
    }
}
