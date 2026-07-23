<?php

namespace App\Http\Middleware;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Client\SocialAuthController;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\ProductReview;
use App\Models\RefundRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'site' => [
                'companyName' => config('asrtech.company_name'),
                'tagline' => config('asrtech.tagline'),
                'supportEmail' => config('asrtech.support_email'),
                'phone' => config('asrtech.phone'),
                'address' => config('asrtech.address'),
                'logoUrl' => config('asrtech.logo_url'),
                'logoLightUrl' => config('asrtech.logo_light_url'),
                'logoDarkUrl' => config('asrtech.logo_dark_url'),
                'currency' => config('asrtech.currency'),
                'social' => config('asrtech.social'),
                'seo' => config('asrtech.seo'),
                'allowRegistration' => (bool) config('asrtech.allow_registration', true),
                'requireTosAccept' => (bool) config('asrtech.require_tos_accept', false),
                'termsUrl' => config('asrtech.terms_url'),
            ],
            'auth' => [
                'user' => $request->user(),
                'admin' => $request->user('admin'),
            ],
            'adminPermissions' => fn () => $request->user('admin') instanceof Admin
                ? $request->user('admin')->role->permissions()
                : null,
            'cartState' => fn () => [
                'count' => app(CartService::class)->count(),
            ],
            // Sidebar notification counters, admin panel only.
            'adminBadges' => fn () => $request->user('admin') !== null ? [
                'unansweredTickets' => Ticket::query()->awaitingReply()->count(),
                'pendingRefundRequests' => RefundRequest::query()->where('status', 'pending')->count(),
                'pendingProductReviews' => ProductReview::query()->where('status', 'pending')->count(),
            ] : null,
            // Client-area nav counters (products / tickets / unpaid invoices).
            'clientBadges' => function () use ($request) {
                $user = $request->user();

                if (! $user instanceof User) {
                    return null;
                }

                return [
                    'products' => $user->licenses()->count(),
                    'subscriptions' => $user->subscriptions()->count(),
                    'tickets' => $user->tickets()->count(),
                    'unpaidInvoices' => Invoice::query()
                        ->where('status', InvoiceStatus::Issued)
                        ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
                        ->count(),
                ];
            },
            'socialProviders' => SocialAuthController::enabledProviders(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
