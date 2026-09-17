<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || ! str_starts_with($plain, ApiToken::PREFIX)) {
            return response()->json(['message' => 'Missing or malformed API token.'], 401);
        }

        $token = ApiToken::query()
            ->where('token_hash', ApiToken::hash($plain))
            ->whereNull('revoked_at')
            ->first();

        if ($token === null) {
            return response()->json(['message' => 'Invalid or revoked API token.'], 401);
        }

        // Cheap throttle: persist usage at most once per minute.
        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->newQuery()->whereKey($token->id)->update([
                'last_used_at' => now(),
                'last_used_ip' => $request->ip(),
            ]);
        }

        $request->attributes->set('apiToken', $token);

        return $next($request);
    }
}
