<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BuildsAccountSummary
{
    /**
     * Account summary card data, matching the dashboard card.
     *
     * @return array<string, mixed>
     */
    protected function accountSummary(User $user): array
    {
        $due = Invoice::query()
            ->where('status', InvoiceStatus::Issued)
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('order:id,amount,setup_fee,tax_amount')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->order->totalAmount());

        return [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'address' => array_values(array_filter([
                    $user->address_1,
                    $user->address_2,
                    trim(implode(' ', array_filter([$user->postcode, $user->city]))),
                    trim(implode(', ', array_filter([$user->state, $user->country]))),
                ])),
            ],
            'totalDue' => number_format($due, 2, '.', ''),
            'currency' => config('asrtech.currency', 'USD'),
        ];
    }
}
