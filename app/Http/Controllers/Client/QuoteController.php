<?php

namespace App\Http\Controllers\Client;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Client\Concerns\BuildsAccountSummary;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\ClientNotification;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    use BuildsAccountSummary;

    public function __construct(private readonly QuoteService $quotes) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Client/Account/Quotes', [
            ...$this->accountSummary($user),
            'quotes' => $user->quotes()
                ->where('status', '!=', QuoteStatus::Draft)
                ->withCount('items')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Quote $quote): array => [
                    'id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'status' => $quote->status->value,
                    'currency' => $quote->currency,
                    'total' => (float) $quote->total,
                    'items_count' => $quote->items_count,
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'actionable' => $quote->isActionable(),
                    'created_at' => $quote->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function show(Request $request, Quote $quote): Response
    {
        $this->authorizeQuote($request, $quote);
        $quote->loadMissing('items');

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Client/Account/Quote', [
            ...$this->accountSummary($user),
            'quote' => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'status' => $quote->status->value,
                'currency' => $quote->currency,
                'subtotal' => (float) $quote->subtotal,
                'tax_amount' => (float) $quote->tax_amount,
                'total' => (float) $quote->total,
                'admin_note' => $quote->admin_note,
                'valid_until' => $quote->valid_until?->toDateString(),
                'actionable' => $quote->isActionable(),
                'order_id' => $quote->order_id,
                'items' => $quote->items->map(fn ($item): array => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'billing_cycle' => $item->billing_cycle->value,
                ])->all(),
            ],
        ]);
    }

    public function accept(Request $request, Quote $quote): RedirectResponse
    {
        $order = DB::transaction(function () use ($request, $quote): ?Order {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->authorizeQuote($request, $quote);
            if ($quote->status === QuoteStatus::Converted && $quote->order_id !== null) {
                return $quote->order()->where('user_id', $quote->user_id)->firstOrFail();
            }
            if ($quote->isExpired() && $quote->status === QuoteStatus::Sent) {
                $quote->update(['status' => QuoteStatus::Expired]);
            }
            if (! $quote->isActionable()) {
                return null;
            }
            $order = $this->quotes->convertToOrder($quote);
            $invoice = $order->invoice()->firstOrFail();
            $quote->user->notify(new ClientNotification(
                title: __('Quote accepted'),
                message: __('Quote :number was converted to an invoice — complete payment to activate your order.', [
                    'number' => $quote->quote_number,
                ]),
                url: route('account.invoice', $invoice),
                level: 'success',
            ));

            return $order;
        });
        if ($order === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This quote is no longer available.')]);

            return redirect()->route('account.quotes.show', $quote);
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote accepted — an invoice has been issued for payment.')]);

        return redirect()->route('account.invoice', $order->invoice()->firstOrFail());
    }

    public function decline(Request $request, Quote $quote): RedirectResponse
    {
        DB::transaction(function () use ($request, $quote): void {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->authorizeQuote($request, $quote);
            if ($quote->isActionable()) {
                $quote->update([
                    'status' => QuoteStatus::Declined,
                    'responded_at' => now(),
                    'client_note' => ($note = (string) $request->string('client_note')->trim()) !== ''
                        ? mb_substr($note, 0, 2000)
                        : null,
                ]);
                Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote declined.')]);
            }
        });

        return redirect()->route('account.quotes.index');
    }

    private function authorizeQuote(Request $request, Quote $quote): void
    {
        abort_unless($quote->user_id === $request->user()?->id && $quote->status !== QuoteStatus::Draft, 404);
    }
}
