<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InvoiceService;
use App\Services\RefundService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly CheckoutService $checkout,
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        return Inertia::render('Admin/Invoices/Index', [
            'filters' => compact('search', 'status'),
            'statuses' => InvoiceStatus::values(),
            'invoices' => Invoice::query()
                ->with(['order.user:id,name,email', 'order.product:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('order.user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('order', fn ($query) => $query
                            ->where('order_number', 'like', "%{$search}%"));
                }))
                ->latest('id')
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString(),
        ]);
    }

    public function store(Order $order): RedirectResponse
    {
        $invoice = $this->invoices->createForOrder($order);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice :number ready.', ['number' => $invoice->invoice_number]),
        ]);

        return redirect()->route('admin.invoices.show', $invoice);
    }

    /**
     * Canonical location is the user-scoped manage page.
     */
    public function show(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('admin.users.invoice', [
            $invoice->order->user_id,
            $invoice->id,
        ]);
    }

    public function manage(User $user, Invoice $invoice): Response
    {
        abort_unless($invoice->order->user_id === $user->id, 404);

        $invoice->load([
            'order.user:id,name,email',
            'order.product:id,name,type',
            'order.productPrice:id,name,billing_cycle',
            'order.license:id,order_id,license_key',
            'refunds.admin:id,name',
            'refunds.creditNote',
        ]);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'transactions' => $invoice->order->transactions()->latest('id')->get(),
            'paymentMethods' => $user->paymentMethods()
                ->latest()
                ->get(['id', 'gateway', 'card_brand', 'card_last_four']),
            'refundSettings' => [
                'refundable_amount' => number_format($invoice->order->refundableAmount(), 2, '.', ''),
                'automatic' => $this->refunds->supportsAutomatic($invoice->order),
            ],
        ]);
    }

    public function addPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'gateway' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($invoice->status !== InvoiceStatus::Issued) {
            throw ValidationException::withMessages([
                'invoice' => __('Payments can only be added to unpaid invoices.'),
            ]);
        }

        if ($invoice->order->status !== OrderStatus::Paid) {
            $this->checkout->markPaid($invoice->order, $data['gateway'], $data['reference'] ?? null);
        }

        $invoice->update(['status' => InvoiceStatus::Paid, 'due_at' => null]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment recorded — invoice :number is paid.', ['number' => $invoice->invoice_number]),
        ]);

        return $this->backToManage($invoice);
    }

    public function refund(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['nullable', 'uuid'],
            'record_only' => ['nullable', 'boolean'],
            'revoke_access' => ['nullable', 'boolean'],
        ]);

        $admin = $request->user('admin');
        abort_unless($admin instanceof Admin, 403);
        $automatic = $this->refunds->supportsAutomatic($invoice->order);
        $refund = $this->refunds->issue(
            $invoice,
            $admin,
            (float) ($data['amount'] ?? $invoice->order->refundableAmount()),
            (string) ($data['reason'] ?? 'Full refund issued by administrator'),
            (string) ($data['idempotency_key'] ?? Str::uuid()),
            (bool) ($data['record_only'] ?? ! $automatic),
            (bool) ($data['revoke_access'] ?? false),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Refund :refund accepted. Credit note :credit is ready.', [
                'refund' => $refund->refund_number,
                'credit' => $refund->creditNote?->credit_note_number,
            ]),
        ]);

        return $this->backToManage($invoice);
    }

    public function updateNotes(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $invoice->update(['notes' => $data['notes']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice notes saved.')]);

        return $this->backToManage($invoice);
    }

    public function sendEmail(Invoice $invoice): RedirectResponse
    {
        Mail::to($invoice->order->user->email)->send(new InvoiceMail($invoice));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice emailed to :email with the PDF attached.', ['email' => $invoice->order->user->email]),
        ]);

        return $this->backToManage($invoice);
    }

    private function backToManage(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('admin.users.invoice', [$invoice->order->user_id, $invoice->id]);
    }

    public function download(Invoice $invoice): SymfonyResponse
    {
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

    /**
     * WHMCS "Add Payment": settle the invoice's order — which also
     * provisions the license — and flag the invoice paid.
     */
    public function markPaid(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return $this->backToManage($invoice);
        }

        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages([
                'invoice' => __('Void invoices cannot be marked paid.'),
            ]);
        }

        if ($invoice->order->status !== OrderStatus::Paid) {
            $this->checkout->markPaid($invoice->order, 'manual');
        }

        $invoice->update(['status' => InvoiceStatus::Paid, 'due_at' => null]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice :number marked paid.', ['number' => $invoice->invoice_number]),
        ]);

        return $this->backToManage($invoice);
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice' => __('Paid invoices cannot be voided.'),
            ]);
        }

        $invoice->update(['status' => InvoiceStatus::Void]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice :number voided.', ['number' => $invoice->invoice_number]),
        ]);

        return $this->backToManage($invoice);
    }
}
