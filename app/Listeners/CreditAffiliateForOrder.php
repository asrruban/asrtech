<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Http\Middleware\TrackAffiliateReferral;
use App\Services\AffiliateService;

class CreditAffiliateForOrder
{
    public function __construct(private readonly AffiliateService $affiliates) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('user');
        $user = $order->user;

        if ($user === null) {
            return;
        }

        // Late attribution: registered earlier, bought via a referral link now.
        if ($user->referred_by_affiliate_id === null) {
            $this->affiliates->attribute(
                $user,
                TrackAffiliateReferral::affiliateFromRequest(request(), $user->id),
            );
            $user->refresh();
        }

        $this->affiliates->creditForOrder($order);
    }
}
