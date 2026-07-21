<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RefundRequestController extends Controller
{
    public function __construct(
        private readonly RefundRequestService $requests,
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status', RefundRequestStatus::Pending->value)->toString();
        $search = $request->string('search')->trim()->toString();
        validator(['status' => $status], ['status' => ['nullable', Rule::enum(RefundRequestStatus::class)]])->validate();

        return Inertia::render('Admin/RefundRequests/Index', [
            'filters' => compact('status', 'search'),
            'statusOptions' => RefundRequestStatus::values(),
            'requests' => RefundRequest::query()
                ->with(['user:id,name,email', 'invoice:id,invoice_number,order_id', 'invoice.order:id,product_id', 'invoice.order.product:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('request_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($query) => $query->where('invoice_number', 'like', "%{$search}%"));
                }))
                ->latest('submitted_at')
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString(),
        ]);
    }

    public function show(RefundRequest $refundRequest): Response
    {
        $refundRequest->load([
            'user:id,name,email', 'decidedBy:id,name', 'refund.creditNote',
            'invoice.order.product:id,name', 'invoice.order.user:id,name,email',
        ]);

        return Inertia::render('Admin/RefundRequests/Show', [
            'refundRequest' => $refundRequest,
            'refundableAmount' => number_format($refundRequest->invoice->order->refundableAmount(), 2, '.', ''),
            'automatic' => $this->refunds->supportsAutomatic($refundRequest->invoice->order),
        ]);
    }

    public function approve(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $data = $request->validate([
            'record_only' => ['required', 'boolean'],
            'revoke_access' => ['required', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $admin = $request->user('admin');
        abort_unless($admin instanceof Admin, 403);

        $this->requests->approve($refundRequest, $admin, (bool) $data['record_only'], (bool) $data['revoke_access'], $data['admin_note'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request approved and processed.')]);

        return back();
    }

    public function reject(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'min:3', 'max:2000']]);
        $admin = $request->user('admin');
        abort_unless($admin instanceof Admin, 403);

        $this->requests->reject($refundRequest, $admin, $data['admin_note']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request rejected.')]);

        return back();
    }
}
