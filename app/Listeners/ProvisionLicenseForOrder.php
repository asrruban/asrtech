<?php

namespace App\Listeners;

use App\Enums\BillingCycle;
use App\Enums\LicenseStatus;
use App\Events\OrderPaid;
use App\Models\License;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ProvisionLicenseForOrder
{
    public function handle(OrderPaid $event): void
    {
        if (! config('asrtech.auto_provision_licenses', true)) {
            return;
        }

        $order = $event->order->loadMissing('items');

        // Renewal orders extend their existing license in SubscriptionService.
        if ($order->subscription_id !== null) {
            return;
        }

        if ($order->items->isNotEmpty()) {
            foreach ($order->items as $item) {
                if ($order->licenses()->where('product_id', $item->product_id)->exists()) {
                    continue;
                }

                License::query()->create([
                    'user_id' => $order->user_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'license_key' => $this->generateKey(),
                    'status' => LicenseStatus::Active,
                    'expires_at' => $this->expiresAt($item->billing_cycle, $order->paid_at, $order),
                ]);
            }

            return;
        }

        if ($order->license()->exists()) {
            return;
        }

        License::query()->create([
            'user_id' => $order->user_id,
            'product_id' => $order->product_id,
            'order_id' => $order->id,
            'license_key' => $this->generateKey(),
            'status' => LicenseStatus::Active,
            'expires_at' => $this->expiresAt($order->billing_cycle, $order->paid_at, $order),
        ]);
    }

    private function generateKey(): string
    {
        do {
            $key = 'ASR-'.implode('-', array_map(
                static fn (): string => Str::upper(Str::random(5)),
                range(1, 3),
            ));
        } while (License::query()->where('license_key', $key)->exists());

        return $key;
    }

    private function expiresAt(BillingCycle $cycle, ?CarbonInterface $paidAt, \App\Models\Order $order): ?CarbonInterface
    {
        $paidAt ??= now();

        if ($order->payment_method === 'free_trial') {
            return $paidAt->copy()->addDays(7);
        }

        return match ($cycle) {
            BillingCycle::Monthly => $paidAt->addMonth(),
            BillingCycle::Yearly => $paidAt->addYear(),
            BillingCycle::OneTime => null,
        };
    }
}
