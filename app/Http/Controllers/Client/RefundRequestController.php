<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\RefundRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefundRequestController extends Controller
{
    public function __construct(private readonly RefundRequestService $requests) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $invoice->order->user_id === $user->id, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $refundRequest = $this->requests->submit($invoice, $user, (float) $data['amount'], $data['reason'], $data['idempotency_key']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request :number submitted.', ['number' => $refundRequest->request_number])]);

        return back();
    }

    public function destroy(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->requests->cancel($refundRequest, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request cancelled.')]);

        return back();
    }
}
