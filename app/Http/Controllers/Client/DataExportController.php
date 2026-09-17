<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GDPR self-service: the client downloads everything we hold about them
 * as a single JSON document. Account deletion lives in
 * AccountDetailsController::destroy.
 */
class DataExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing([
            'orders.items',
            'orders.invoice',
            'licenses.product:id,name',
            'subscriptions.product:id,name',
            'tickets.replies',
            'productReviews',
            'refundRequests',
            'paymentMethods',
        ]);

        $export = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'company_name' => $user->company_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => array_filter([
                    $user->address_1, $user->address_2, $user->city,
                    $user->state, $user->postcode, $user->country,
                ]),
                'vat_number' => $user->vat_number,
                'newsletter' => $user->newsletter,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'registered_at' => $user->created_at?->toIso8601String(),
            ],
            'orders' => $user->orders->map(fn ($order): array => [
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'total' => $order->totalAmount(),
                'currency' => $order->currency,
                'payment_method' => $order->payment_method,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item): array => [
                    'product' => $item->product_name,
                    'amount' => (float) $item->amount,
                    'billing_cycle' => $item->billing_cycle->value,
                ])->all(),
                'invoices' => $order->invoice !== null ? [[
                    'invoice_number' => $order->invoice->invoice_number,
                    'status' => $order->invoice->status->value,
                    'issued_at' => $order->invoice->issued_at->toIso8601String(),
                ]] : [],
            ])->all(),
            'licenses' => $user->licenses->map(fn ($license): array => [
                'license_key' => $license->license_key,
                'product' => $license->product?->name,
                'status' => $license->status->value,
                'domain' => $license->domain,
                'expires_at' => $license->expires_at?->toIso8601String(),
            ])->all(),
            'subscriptions' => $user->subscriptions->map(fn ($subscription): array => [
                'product' => $subscription->product?->name,
                'status' => $subscription->status->value,
                'billing_cycle' => $subscription->billing_cycle->value,
                'amount' => (float) $subscription->amount,
                'currency' => $subscription->currency,
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            ])->all(),
            'tickets' => $user->tickets->map(fn (Ticket $ticket): array => [
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'replies' => $ticket->replies->map(fn (TicketReply $reply): array => [
                    'message' => $reply->message,
                    'is_staff' => $reply->admin_id !== null,
                    'created_at' => $reply->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'reviews' => $user->productReviews->map(fn ($review): array => [
                'rating' => $review->rating,
                'title' => $review->title,
                'content' => $review->content,
                'status' => $review->status->value,
            ])->all(),
            'refund_requests' => $user->refundRequests->map(fn ($refundRequest): array => [
                'request_number' => $refundRequest->request_number,
                'amount' => (float) $refundRequest->amount,
                'currency' => $refundRequest->currency,
                'status' => $refundRequest->status->value,
                'reason' => $refundRequest->reason,
            ])->all(),
            'notifications' => $user->notifications()->latest()->limit(100)->get()
                ->map(fn ($notification): array => [
                    'title' => $notification->data['title'] ?? '',
                    'message' => $notification->data['message'] ?? '',
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])->all(),
        ];

        $filename = 'personal-data-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($export): void {
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
