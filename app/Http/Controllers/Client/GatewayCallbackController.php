<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Payments\GatewayRegistry;
use App\Services\GatewayWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatches gateway webhooks and payment returns to the callback
 * handler living beside the gateway module, WHMCS-callback style.
 */
class GatewayCallbackController extends Controller
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly GatewayWebhookService $webhooks,
    ) {}

    public function webhook(Request $request, string $gateway): Response
    {
        $handler = $this->gateways->callback($gateway);

        abort_if($handler === null || ! method_exists($handler, 'webhook'), 404);

        return $this->webhooks->receive(
            $gateway,
            $request,
            fn (): Response => $handler->webhook($request),
        );
    }

    public function return(Request $request, string $gateway): RedirectResponse
    {
        $handler = $this->gateways->callback($gateway);

        abort_if($handler === null || ! method_exists($handler, 'handleReturn'), 404);

        return $handler->handleReturn($request);
    }
}
