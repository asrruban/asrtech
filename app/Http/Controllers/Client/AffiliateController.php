<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\BuildsAccountSummary;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Referral;
use App\Models\User;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    use BuildsAccountSummary;

    public function __construct(private readonly AffiliateService $affiliates) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $affiliate = Affiliate::query()->where('user_id', $user->id)->first();

        return Inertia::render('Client/Account/Affiliate', [
            ...$this->accountSummary($user),
            'affiliate' => $affiliate !== null ? [
                'code' => $affiliate->code,
                'active' => $affiliate->active,
                'referral_url' => $affiliate->referralUrl(),
                'commission_rate' => (float) $affiliate->commission_rate,
                'pending_balance' => (float) $affiliate->pending_balance,
                'approved_balance' => (float) $affiliate->approved_balance,
                'paid_balance' => (float) $affiliate->paid_balance,
            ] : null,
            'referrals' => $affiliate !== null
                ? $affiliate->referrals()
                    ->with(['referredUser:id,name', 'order:id,order_number,currency'])
                    ->orderByDesc('created_at')
                    ->paginate(10)
                    ->through(fn (Referral $referral): array => [
                        'id' => $referral->id,
                        'referred' => $referral->referredUser?->name,
                        'order_number' => $referral->order?->order_number,
                        'order_total' => (float) $referral->order_total,
                        'commission' => (float) $referral->commission_amount,
                        'status' => $referral->status->value,
                        'created_at' => $referral->created_at?->toIso8601String(),
                    ])
                : null,
            'defaultRate' => (float) config('asrtech.affiliates.default_commission_rate', 10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->affiliates->join($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to the affiliate program! Share your link to start earning.')]);

        return redirect()->route('account.affiliate');
    }
}
