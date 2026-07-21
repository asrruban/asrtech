<?php

namespace App\Http\Controllers\Client;

use App\Enums\InvoiceStatus;
use App\Enums\LicenseStatus;
use App\Enums\ProductType;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\LicenseResource;
use App\Http\Resources\Client\OrderResource;
use App\Models\Invoice;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\Ticket;
use App\Models\User;
use App\Services\RefundRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * WHMCS-style client area (/client-area): dashboard with account card,
 * amount due, and active products; dedicated pages for products,
 * invoices, and their detail views.
 */
class AccountController extends Controller
{
    public function __construct(private readonly RefundRequestService $refundRequests) {}

    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $dueInvoices = $this->invoicesQuery($user)
            ->where('status', InvoiceStatus::Issued)
            ->get();

        return Inertia::render('Client/Account/Index', [
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
            'totalDue' => number_format(
                $dueInvoices->sum(fn (Invoice $invoice) => $invoice->order->totalAmount()),
                2, '.', '',
            ),
            'currency' => config('asrtech.currency', 'USD'),
            'dueInvoices' => $this->invoicePayload($dueInvoices),
            'activeProducts' => $user->licenses()
                ->where('status', 'active')
                ->with(['product:id,name,slug,type,featured_image', 'order:id,billing_cycle'])
                ->latest()
                ->get()
                ->map(fn (License $license) => [
                    'id' => $license->id,
                    'product' => $license->product === null ? null : [
                        ...$license->product->only(['name', 'slug', 'featured_image']),
                        'url' => $license->product->storefrontUrl(),
                    ],
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'billing_cycle' => $license->order?->billing_cycle->value,
                ]),
            'tickets' => $this->ticketPayload(
                $user->tickets()
                    ->with('department:id,name')
                    ->where('status', '!=', TicketStatus::Closed)
                    ->orderByDesc('last_reply_at')
                    ->limit(3)
                    ->get(),
            ),
            'orders' => OrderResource::collection(
                $user->orders()
                    ->with(['product:id,name,slug,type', 'license:id,order_id,license_key'])
                    ->latest()
                    ->get(),
            )->resolve(),
        ]);
    }

    /** Full Products/Services list, active services first. */
    public function products(Request $request): Response
    {
        return Inertia::render('Client/Account/Products', [
            'services' => LicenseResource::collection(
                $this->servicesQuery($this->user($request))->get(),
            )->resolve(),
        ]);
    }

    /** Single product/service detail (WHMCS-style product page). */
    public function product(Request $request, License $license): Response
    {
        $user = $this->user($request);
        abort_unless($license->user_id === $user->id, 404);

        $license->load(['product.category:id,name', 'order', 'subscription']);
        $product = $license->product;

        return Inertia::render('Client/Account/Product', [
            'service' => [
                'id' => $license->id,
                'license_key' => $license->license_key,
                'status' => $license->status->value,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'created_at' => $license->created_at?->toIso8601String(),
                'domain' => $license->domain,
                'path' => $license->path,
                'ip_address' => $license->ip_address,
                'reissue_count' => $license->reissue_count,
                'product' => $product === null ? null : [
                    ...$product->only([
                        'name', 'slug', 'type', 'featured_image', 'version',
                        'compatibility', 'php_compatibility',
                    ]),
                    'url' => $product->storefrontUrl(),
                    'release_date' => $product->release_date,
                    'category' => $product->category?->name,
                    'has_changelog' => filled($product->changelog),
                ],
                'order' => [
                    'order_number' => $license->order->order_number,
                    'currency' => $license->order->currency,
                    'amount' => $license->order->amount,
                    'billing_cycle' => $license->order->billing_cycle->value,
                    'payment_method' => $license->order->payment_method,
                    'created_at' => $license->order->created_at?->toIso8601String(),
                ],
                'subscription' => $license->subscription === null ? null : [
                    'id' => $license->subscription->id,
                    'status' => $license->subscription->status->value,
                    'current_period_end' => $license->subscription->current_period_end?->toIso8601String(),
                    'cancel_at_period_end' => $license->subscription->cancel_at_period_end,
                    'url' => route('account.subscriptions'),
                ],
            ],
            'releases' => $product === null ? [] : $this->releasePayload($product, $license),
            // "Available Services" cards — the web development offerings.
            'services' => Product::query()
                ->where('status', true)
                ->where('type', ProductType::WebDevelopment)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug', 'type', 'short_description', 'featured_image'])
                ->map(fn (Product $offering): array => [
                    ...$offering->only(['id', 'name', 'slug', 'short_description', 'featured_image']),
                    'url' => $offering->storefrontUrl(),
                ]),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function releasePayload(Product $product, License $license): array
    {
        $licenseCanDownload = $license->status === LicenseStatus::Active
            && ! $license->expires_at?->isPast();

        $releases = $product->releases()
            ->available()
            ->withCount([
                'downloads as downloads_used' => fn (Builder $query) => $query
                    ->where('license_id', $license->id),
            ])
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProductRelease $release) use ($license, $licenseCanDownload): array {
                $downloadsUsed = (int) $release->getAttribute('downloads_used');
                $remaining = $release->download_limit === null
                    ? null
                    : max(0, $release->download_limit - $downloadsUsed);

                return [
                    'id' => $release->id,
                    'version' => $release->version,
                    'title' => $release->title,
                    'release_notes' => $release->release_notes,
                    'original_filename' => $release->original_filename,
                    'file_size' => $release->file_size,
                    'checksum_sha256' => $release->checksum_sha256,
                    'released_at' => $release->released_at->toIso8601String(),
                    'available_until' => $release->available_until?->toIso8601String(),
                    'download_limit' => $release->download_limit,
                    'downloads_used' => $downloadsUsed,
                    'downloads_remaining' => $remaining,
                    'can_download' => $licenseCanDownload && ($remaining === null || $remaining > 0),
                    'blocked_reason' => match (true) {
                        $license->status !== LicenseStatus::Active => 'An active license is required.',
                        $license->expires_at?->isPast() => 'This license has expired.',
                        $remaining === 0 => 'The download limit has been reached.',
                        default => null,
                    },
                    'download_url' => route('account.releases.download', [
                        'license' => $license,
                        'release' => $release,
                    ]),
                ];
            })
            ->all();

        return array_values($releases);
    }

    /** Full invoice history. */
    public function invoices(Request $request): Response
    {
        return Inertia::render('Client/Account/Invoices', [
            'invoices' => $this->invoicePayload(
                $this->invoicesQuery($this->user($request))->get(),
            ),
        ]);
    }

    /** Single invoice detail. */
    public function invoice(Request $request, Invoice $invoice): Response
    {
        $user = $this->user($request);
        abort_unless($invoice->order->user_id === $user->id, 404);

        $invoice->load([
            'order.product:id,name,slug,type',
            'order.productPrice:id,name,billing_cycle',
            'order.license:id,order_id,license_key',
            'creditNotes.refund:id,status,gateway,refund_number',
            'refundRequests' => fn ($query) => $query->with(['refund.creditNote', 'decidedBy:id,name'])->latest('submitted_at'),
        ]);

        return Inertia::render('Client/Account/Invoice', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'issued_at' => $invoice->issued_at->toIso8601String(),
                'due_at' => $invoice->due_at?->toIso8601String(),
                'notes' => $invoice->notes,
                'currency' => $invoice->order->currency,
                'subtotal' => $invoice->order->subtotal,
                'amount' => $invoice->order->amount,
                'discount_amount' => $invoice->order->discount_amount,
                'setup_fee' => $invoice->order->setup_fee,
                'tax_amount' => $invoice->order->tax_amount,
                'tax_name' => $invoice->order->tax_name,
                'tax_rate' => $invoice->order->tax_rate,
                'promotion_code' => $invoice->order->promotion_code,
                'total' => number_format($invoice->order->totalAmount(), 2, '.', ''),
                'order_number' => $invoice->order->order_number,
                'billing_cycle' => $invoice->order->billing_cycle->value,
                'product' => $invoice->order->product === null ? null : [
                    ...$invoice->order->product->only(['name', 'slug']),
                    'url' => $invoice->order->product->storefrontUrl(),
                ],
                'license_key' => $invoice->order->license?->license_key,
                'credit_notes' => $invoice->creditNotes->map(fn ($creditNote): array => [
                    'id' => $creditNote->id,
                    'credit_note_number' => $creditNote->credit_note_number,
                    'total_amount' => $creditNote->total_amount,
                    'issued_at' => $creditNote->issued_at->toIso8601String(),
                    'refund_status' => $creditNote->refund->status->value,
                ])->all(),
                'refund_requests' => $invoice->refundRequests->map(fn ($refundRequest): array => [
                    'id' => $refundRequest->id,
                    'request_number' => $refundRequest->request_number,
                    'amount' => $refundRequest->amount,
                    'status' => $refundRequest->status->value,
                    'reason' => $refundRequest->reason,
                    'admin_note' => $refundRequest->admin_note,
                    'submitted_at' => $refundRequest->submitted_at->toIso8601String(),
                    'decided_at' => $refundRequest->decided_at?->toIso8601String(),
                    'credit_note_id' => $refundRequest->refund?->creditNote?->id,
                ])->all(),
            ],
            'refundPolicy' => $this->refundRequests->eligibility($invoice, $user),
            'billTo' => [
                'name' => $user->name,
                'email' => $user->email,
                'address' => array_values(array_filter([
                    $user->address_1,
                    $user->address_2,
                    trim(implode(' ', array_filter([$user->postcode, $user->city]))),
                    trim(implode(', ', array_filter([$user->state, $user->country]))),
                ])),
            ],
        ]);
    }

    public function downloadInvoice(Request $request, Invoice $invoice): SymfonyResponse
    {
        $user = $this->user($request);

        // Invoices are private — respond 404 for anyone but the owner.
        abort_unless($invoice->order->user_id === $user->id, 404);

        $invoice->load([
            'order.user:id,name,email',
            'order.product:id,name',
            'order.productPrice:id,name,billing_cycle',
            'order.license:id,order_id,license_key',
        ]);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'payTo' => config('asrtech.invoice.pay_to'),
            'footerNote' => config('asrtech.invoice.footer_note'),
        ])->download("{$invoice->invoice_number}.pdf");
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Licenses with products, active status first then newest.
     *
     * @return Builder<License>
     */
    private function servicesQuery(User $user): Builder
    {
        return $user->licenses()
            ->with(['product:id,name,slug,type,featured_image', 'order:id,order_number'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest()
            ->getQuery();
    }

    /** @return Builder<Invoice> */
    private function invoicesQuery(User $user): Builder
    {
        return Invoice::query()
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(['order:id,order_number,currency,amount,setup_fee,tax_amount,product_id', 'order.product:id,name,slug,type'])
            ->orderByDesc('issued_at');
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function invoicePayload($invoices): array
    {
        return $invoices->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value,
            'issued_at' => $invoice->issued_at->toIso8601String(),
            'due_at' => $invoice->due_at?->toIso8601String(),
            'currency' => $invoice->order->currency,
            'total' => number_format($invoice->order->totalAmount(), 2, '.', ''),
            'order_number' => $invoice->order->order_number,
            'product' => $invoice->order->product === null ? null : [
                ...$invoice->order->product->only(['name', 'slug']),
                'url' => $invoice->order->product->storefrontUrl(),
            ],
        ])->all();
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return array<int, array<string, mixed>>
     */
    private function ticketPayload($tickets): array
    {
        return $tickets->map(fn (Ticket $ticket) => [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'department' => $ticket->department?->name,
            'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
        ])->all();
    }
}
