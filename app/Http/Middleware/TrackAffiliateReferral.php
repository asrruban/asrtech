<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capture ?ref=CODE affiliate links into a long-lived cookie.
 * The code is attributed at registration (or first paid order).
 */
class TrackAffiliateReferral
{
    public const COOKIE = 'aff_ref';

    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query('ref');

        $response = $next($request);

        if (is_string($code) && preg_match('/^[A-Za-z0-9]{4,24}$/', $code)) {
            $days = (int) config('asrtech.affiliates.cookie_days', 30);
            $response->cookie(cookie(
                self::COOKIE,
                strtoupper($code),
                $days * 24 * 60,
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $response;
    }

    /** Resolve the cookie to an active affiliate (self-referrals excluded). */
    public static function affiliateFromRequest(Request $request, ?int $excludeUserId = null): ?Affiliate
    {
        $code = $request->cookie(self::COOKIE);

        if (! is_string($code) || $code === '') {
            return null;
        }

        $affiliate = Affiliate::query()
            ->where('code', strtoupper($code))
            ->where('active', true)
            ->first();

        if ($affiliate === null || $affiliate->user_id === $excludeUserId) {
            return null;
        }

        return $affiliate;
    }
}
