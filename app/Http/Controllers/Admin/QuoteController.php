<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Mail\QuoteMail;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectInquiry;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Commerce/Quotes/Index', [
            'quotes' => Quote::query()
                ->with(['user:id,name,email', 'items'])
                ->orderByDesc('created_at')
                ->paginate(15)
                ->through(fn (Quote $quote): array => [
                    'id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'client' => $quote->user?->only(['id', 'name', 'email']),
                    'status' => $quote->status->value,
                    'currency' => $quote->currency,
                    'subtotal' => (float) $quote->subtotal,
                    'tax_amount' => (float) $quote->tax_amount,
                    'total' => (float) $quote->total,
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'admin_note' => $quote->admin_note,
                    'items' => $quote->items->map(fn ($item): array => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'billing_cycle' => $item->billing_cycle->value,
                    ])->all(),
                    'order_id' => $quote->order_id,
                    'created_at' => $quote->created_at?->toIso8601String(),
                ]),
            'statuses' => QuoteStatus::values(),
            'products' => Product::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                ]),
            'clients' => User::query()
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'email']),
            'currency' => (string) config('asrtech.currency', 'USD'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $quote = Quote::query()->create([
            'quote_number' => $this->quotes->generateQuoteNumber(),
            'user_id' => $data['user_id'],
            'status' => QuoteStatus::Draft,
            'currency' => (string) config('asrtech.currency', 'USD'),
            'admin_note' => $data['admin_note'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        $this->quotes->syncItems($quote, $this->items($data), (float) ($data['tax_rate'] ?? 0));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote :number created.', ['number' => $quote->quote_number])]);

        return redirect()->route('admin.quotes.index');
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($quote, $data): void {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if (! in_array($quote->status, [QuoteStatus::Draft, QuoteStatus::Sent], true)) {
                throw ValidationException::withMessages(['quote' => __('Only draft or sent quotes can be edited.')]);
            }
            if ((int) $data['user_id'] !== (int) $quote->user_id && (
                ProjectInquiry::query()->where('quote_id', $quote->id)->exists()
                || Project::query()->where('quote_id', $quote->id)->exists()
                || MaintenanceRequest::query()->where('quote_id', $quote->id)->exists()
            )) {
                throw ValidationException::withMessages(['user_id' => 'The client cannot change while this quote is linked to an inquiry, project or maintenance request.']);
            }
            $quote->update([
                'user_id' => $data['user_id'],
                'admin_note' => $data['admin_note'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
            ]);
            $this->quotes->syncItems($quote, $this->items($data), (float) ($data['tax_rate'] ?? 0));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote updated.')]);

        return redirect()->route('admin.quotes.index');
    }

    public function send(Quote $quote): RedirectResponse
    {
        if (! in_array($quote->status, [QuoteStatus::Draft, QuoteStatus::Sent], true)) {
            throw ValidationException::withMessages(['quote' => __('This quote cannot be sent.')]);
        }

        $quote->loadMissing(['user', 'items']);

        try {
            Mail::to($quote->user->email)->send(new QuoteMail($quote));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['quote' => __('Failed to send the quote email. Please try again.')]);
        }

        $quote->update([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote sent to :email.', ['email' => $quote->user->email])]);

        return redirect()->route('admin.quotes.index');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        abort_if($quote->status === QuoteStatus::Converted, 422);

        $quote->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote deleted.')]);

        return redirect()->route('admin.quotes.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'valid_until' => ['nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.billing_cycle' => ['required', Rule::in(['one_time', 'monthly', 'yearly'])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{product_id: int, product_name: string, quantity: int, unit_price: float, billing_cycle: string}>
     */
    private function items(array $data): array
    {
        $rows = (array) ($data['items'] ?? []);
        $ids = array_map(fn (array $item): int => (int) $item['product_id'], $rows);

        $names = Product::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id');

        $items = [];

        foreach ($rows as $item) {
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) ($names[$item['product_id']] ?? __('Custom item')),
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'billing_cycle' => (string) $item['billing_cycle'],
            ];
        }

        return $items;
    }
}
