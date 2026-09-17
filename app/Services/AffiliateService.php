<?php

namespace App\Services;

use App\Enums\ReferralStatus;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    /** Create an affiliate account for a client. */
    public function join(User $user): Affiliate
    {
        return Affiliate::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'code' => Affiliate::generateCode(),
                'commission_rate' => (float) config('asrtech.affiliates.default_commission_rate', 10),
            ],
        );
    }

    /** Link a user to the affiliate that referred them (once). */
    public function attribute(User $user, ?Affiliate $affiliate): void
    {
        if ($affiliate === null || $user->referred_by_affiliate_id !== null) {
            return;
        }

        if ($affiliate->user_id === $user->id) {
            return;
        }

        $user->forceFill(['referred_by_affiliate_id' => $affiliate->id])->save();
    }

    /**
     * Credit the affiliate for a paid order. Idempotent per order
     * (referrals.order_id is unique).
     */
    public function creditForOrder(Order $order): ?Referral
    {
        $order->loadMissing('user');

        $user = $order->user;

        if ($user === null || $user->referred_by_affiliate_id === null) {
            return null;
        }

        $affiliate = Affiliate::query()
            ->whereKey($user->referred_by_affiliate_id)
            ->where('active', true)
            ->first();

        if ($affiliate === null || $affiliate->user_id === $user->id) {
            return null;
        }

        return DB::transaction(function () use ($order, $user, $affiliate): ?Referral {
            $existing = Referral::query()->where('order_id', $order->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            $total = round((float) $order->amount + (float) $order->setup_fee, 2);
            $commission = round($total * ((float) $affiliate->commission_rate / 100), 2);

            if ($commission <= 0) {
                return null;
            }

            $referral = Referral::query()->create([
                'affiliate_id' => $affiliate->id,
                'referred_user_id' => $user->id,
                'order_id' => $order->id,
                'order_total' => number_format($total, 2, '.', ''),
                'commission_amount' => number_format($commission, 2, '.', ''),
                'status' => ReferralStatus::Pending,
            ]);

            $affiliate->newQuery()
                ->whereKey($affiliate->id)
                ->increment('pending_balance', $commission);

            return $referral;
        });
    }

    public function approve(Referral $referral): void
    {
        DB::transaction(function () use ($referral): void {
            $updated = Referral::query()
                ->whereKey($referral->id)
                ->where('status', ReferralStatus::Pending->value)
                ->update(['status' => ReferralStatus::Approved->value, 'approved_at' => now()]);

            if ($updated === 1) {
                $amount = (float) $referral->commission_amount;
                $referral->affiliate->newQuery()
                    ->whereKey($referral->affiliate_id)
                    ->decrement('pending_balance', $amount);
                $referral->affiliate->newQuery()
                    ->whereKey($referral->affiliate_id)
                    ->increment('approved_balance', $amount);
            }
        });
    }

    public function markPaid(Referral $referral): void
    {
        DB::transaction(function () use ($referral): void {
            $updated = Referral::query()
                ->whereKey($referral->id)
                ->where('status', ReferralStatus::Approved->value)
                ->update(['status' => ReferralStatus::Paid->value, 'paid_at' => now()]);

            if ($updated === 1) {
                $amount = (float) $referral->commission_amount;
                $referral->affiliate->newQuery()
                    ->whereKey($referral->affiliate_id)
                    ->decrement('approved_balance', $amount);
                $referral->affiliate->newQuery()
                    ->whereKey($referral->affiliate_id)
                    ->increment('paid_balance', $amount);
            }
        });
    }
}
