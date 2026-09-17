<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const DATASETS = ['orders', 'invoices', 'transactions', 'users', 'subscriptions'];

    public function index(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $paidOrders = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()]);

        $daily = (clone $paidOrders)
            ->selectRaw('DATE(paid_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(amount + setup_fee + tax_amount) as revenue')
            ->selectRaw('SUM(tax_amount) as tax')
            ->groupBy('day')
            ->orderBy('day')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'day' => (string) $row->day,
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
                'tax' => round((float) $row->tax, 2),
            ]);

        $totalRevenue = round($daily->sum('revenue'), 2);
        $totalOrders = (int) $daily->sum('orders');
        $totalTax = round($daily->sum('tax'), 2);

        $gateways = Transaction::query()
            ->where('type', 'payment')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('gateway')
            ->selectRaw('COUNT(*) as payments')
            ->selectRaw('SUM(amount) as volume')
            ->groupBy('gateway')
            ->orderByDesc('volume')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'gateway' => (string) $row->gateway,
                'payments' => (int) $row->payments,
                'volume' => round((float) $row->volume, 2),
            ]);

        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($query) => $query
                ->where('status', OrderStatus::Paid)
                ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()]))
            ->selectRaw('product_name')
            ->selectRaw('COUNT(*) as sales')
            ->selectRaw('SUM(amount + setup_fee) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'product' => (string) $row->product_name,
                'sales' => (int) $row->sales,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $subscriptions = Subscription::query()
            ->selectRaw('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $mrr = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->get(['billing_cycle', 'amount'])
            ->sum(fn (Subscription $subscription): float => match ($subscription->billing_cycle) {
                BillingCycle::Monthly => (float) $subscription->amount,
                BillingCycle::Yearly => (float) $subscription->amount / 12,
                default => 0.0,
            });

        return Inertia::render('Admin/Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'currency' => (string) config('asrtech.currency', 'USD'),
            'totals' => [
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'tax' => $totalTax,
                'average_order' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0,
            ],
            'daily' => $daily,
            'gateways' => $gateways,
            'topProducts' => $topProducts,
            'subscriptions' => [
                'by_status' => $subscriptions,
                'mrr' => round((float) $mrr, 2),
                'new_in_range' => Subscription::query()
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->count(),
                'past_due' => (int) ($subscriptions[SubscriptionStatus::PastDue->value] ?? 0),
            ],
            'exports' => self::DATASETS,
        ]);
    }

    public function export(Request $request, string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, self::DATASETS, true), 404);

        [$from, $to] = $this->range($request);
        [$columns, $rows] = $this->dataset($dataset, $from, $to);

        $filename = sprintf('%s_%s_%s.csv', $dataset, $from->toDateString(), $to->toDateString());

        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(Request $request): array
    {
        $to = CarbonImmutable::parse($request->query('to', now()->toDateString()))->startOfDay();
        $from = CarbonImmutable::parse($request->query('from', $to->subDays(29)->toDateString()))->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array{list<string>, Collection<int, mixed>}
     */
    private function dataset(string $dataset, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return match ($dataset) {
            'orders' => [
                ['order_number', 'client_name', 'client_email', 'status', 'payment_method', 'subtotal', 'discount', 'setup_fee', 'tax', 'total', 'currency', 'paid_at', 'created_at'],
                Order::query()
                    ->with('user:id,name,email')
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn (Order $order): array => [
                        $order->order_number,
                        $order->user?->name,
                        $order->user?->email,
                        $order->status->value,
                        $order->payment_method,
                        $order->subtotal,
                        $order->discount_amount,
                        $order->setup_fee,
                        $order->tax_amount,
                        number_format($order->totalAmount(), 2, '.', ''),
                        $order->currency,
                        $order->paid_at?->toIso8601String(),
                        $order->created_at?->toIso8601String(),
                    ]),
            ],
            'invoices' => [
                ['invoice_number', 'client_name', 'client_email', 'status', 'total', 'currency', 'issued_at', 'due_at'],
                Invoice::query()
                    ->with('order.user:id,name,email', 'order:id,amount,setup_fee,tax_amount,currency,user_id')
                    ->whereBetween('issued_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('issued_at')
                    ->get()
                    ->map(fn (Invoice $invoice): array => [
                        $invoice->invoice_number,
                        $invoice->order?->user?->name,
                        $invoice->order?->user?->email,
                        $invoice->status->value,
                        $invoice->order !== null ? number_format($invoice->order->totalAmount(), 2, '.', '') : null,
                        $invoice->order?->currency,
                        $invoice->issued_at->toIso8601String(),
                        $invoice->due_at?->toIso8601String(),
                    ]),
            ],
            'transactions' => [
                ['id', 'order_id', 'type', 'gateway', 'reference', 'amount', 'fees', 'created_at'],
                Transaction::query()
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn (Transaction $transaction): array => [
                        $transaction->id,
                        $transaction->order_id,
                        $transaction->type,
                        $transaction->gateway,
                        $transaction->reference,
                        $transaction->amount,
                        $transaction->fees,
                        $transaction->created_at?->toIso8601String(),
                    ]),
            ],
            'users' => [
                ['id', 'name', 'email', 'company', 'country', 'newsletter', 'email_verified_at', 'created_at'],
                User::query()
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn (User $user): array => [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->company_name,
                        $user->country,
                        $user->newsletter ? 'yes' : 'no',
                        $user->email_verified_at?->toIso8601String(),
                        $user->created_at?->toIso8601String(),
                    ]),
            ],
            'subscriptions' => [
                ['id', 'client_email', 'product', 'gateway', 'status', 'billing_cycle', 'amount', 'currency', 'current_period_end', 'dunning_reminders_sent', 'created_at'],
                Subscription::query()
                    ->with('user:id,email', 'product:id,name')
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn (Subscription $subscription): array => [
                        $subscription->id,
                        $subscription->user?->email,
                        $subscription->product?->name,
                        $subscription->gateway,
                        $subscription->status->value,
                        $subscription->billing_cycle->value,
                        $subscription->amount,
                        $subscription->currency,
                        $subscription->current_period_end?->toIso8601String(),
                        $subscription->dunning_reminders_sent,
                        $subscription->created_at?->toIso8601String(),
                    ]),
            ],
            default => abort(404),
        };
    }
}
