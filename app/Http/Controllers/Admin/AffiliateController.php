<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Referral;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    public function __construct(private readonly AffiliateService $affiliates) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Commerce/Affiliates', [
            'affiliates' => Affiliate::query()
                ->with('user:id,name,email')
                ->withCount('referrals')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Affiliate $affiliate): array => [
                    'id' => $affiliate->id,
                    'client' => $affiliate->user?->only(['id', 'name', 'email']),
                    'code' => $affiliate->code,
                    'active' => $affiliate->active,
                    'commission_rate' => (float) $affiliate->commission_rate,
                    'pending_balance' => (float) $affiliate->pending_balance,
                    'approved_balance' => (float) $affiliate->approved_balance,
                    'paid_balance' => (float) $affiliate->paid_balance,
                    'referrals_count' => $affiliate->referrals_count,
                    'created_at' => $affiliate->created_at?->toIso8601String(),
                ]),
            'referrals' => Referral::query()
                ->with(['affiliate.user:id,name', 'referredUser:id,name', 'order:id,order_number,currency'])
                ->orderByDesc('created_at')
                ->paginate(15)
                ->through(fn (Referral $referral): array => [
                    'id' => $referral->id,
                    'affiliate' => $referral->affiliate?->user?->name,
                    'referred' => $referral->referredUser?->name,
                    'order_number' => $referral->order?->order_number,
                    'order_total' => (float) $referral->order_total,
                    'commission' => (float) $referral->commission_amount,
                    'status' => $referral->status->value,
                    'created_at' => $referral->created_at?->toIso8601String(),
                ]),
            'statuses' => ReferralStatus::values(),
        ]);
    }

    public function toggle(Affiliate $affiliate): RedirectResponse
    {
        $affiliate->update(['active' => ! $affiliate->active]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Affiliate updated.')]);

        return redirect()->route('admin.affiliates.index');
    }

    public function approve(Referral $referral): RedirectResponse
    {
        $this->affiliates->approve($referral);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Referral commission approved.')]);

        return redirect()->route('admin.affiliates.index');
    }

    public function pay(Referral $referral): RedirectResponse
    {
        $this->affiliates->markPaid($referral);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Referral commission marked as paid.')]);

        return redirect()->route('admin.affiliates.index');
    }
}
