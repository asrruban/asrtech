<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\License;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global admin search backing the ⌘K command palette.
 * Returns small, permission-filtered result groups.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        /** @var Admin $admin */
        $admin = $request->user('admin');
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $groups = [];

        if ($admin->hasPermission('users.view')) {
            $users = User::query()
                ->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('company_name', 'like', $like))
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'email']);

            if ($users->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Users',
                    'items' => $users->map(fn (User $user): array => [
                        'title' => $user->name,
                        'subtitle' => $user->email,
                        'url' => route('admin.users.show', $user),
                    ])->all(),
                ];
            }
        }

        if ($admin->hasPermission('billing.manage')) {
            $invoices = Invoice::query()
                ->with('order.user:id,name')
                ->where('invoice_number', 'like', $like)
                ->orderByDesc('issued_at')
                ->limit(5)
                ->get();

            if ($invoices->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Invoices',
                    'items' => $invoices->map(fn (Invoice $invoice): array => [
                        'title' => $invoice->invoice_number,
                        'subtitle' => $invoice->order?->user?->name.' · '.$invoice->status->value,
                        'url' => route('admin.invoices.show', $invoice),
                    ])->all(),
                ];
            }

            $subscriptions = Subscription::query()
                ->with(['user:id,name,email', 'product:id,name'])
                ->where(fn ($query) => $query
                    ->where('gateway_subscription_id', 'like', $like)
                    ->orWhereHas('user', fn ($q) => $q->where('email', 'like', $like))
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'like', $like)))
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            if ($subscriptions->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Subscriptions',
                    'items' => $subscriptions->map(fn (Subscription $subscription): array => [
                        'title' => $subscription->product->name.' · '.$subscription->status->value,
                        'subtitle' => $subscription->user?->email,
                        'url' => route('admin.subscriptions.show', $subscription),
                    ])->all(),
                ];
            }
        }

        if ($admin->hasPermission('licenses.view')) {
            $licenses = License::query()
                ->with('product:id,name')
                ->where(fn ($query) => $query
                    ->where('license_key', 'like', $like)
                    ->orWhere('domain', 'like', $like))
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            if ($licenses->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Licenses',
                    'items' => $licenses->map(fn (License $license): array => [
                        'title' => $license->license_key,
                        'subtitle' => $license->product->name.' · '.$license->status->value,
                        'url' => route('admin.licenses.show', $license),
                    ])->all(),
                ];
            }
        }

        if ($admin->hasPermission('support.manage')) {
            $tickets = Ticket::query()
                ->where(fn ($query) => $query
                    ->where('subject', 'like', $like)
                    ->orWhere('ticket_number', 'like', $like))
                ->orderByDesc('last_reply_at')
                ->limit(5)
                ->get(['id', 'ticket_number', 'subject', 'status']);

            if ($tickets->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Tickets',
                    'items' => $tickets->map(fn (Ticket $ticket): array => [
                        'title' => $ticket->subject,
                        'subtitle' => $ticket->ticket_number.' · '.$ticket->status->value,
                        'url' => route('admin.support.tickets.show', $ticket),
                    ])->all(),
                ];
            }
        }

        if ($admin->hasPermission('catalog.manage')) {
            $products = Product::query()
                ->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('sku', 'like', $like))
                ->limit(5)
                ->get(['id', 'name', 'type']);

            if ($products->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Products',
                    'items' => $products->map(fn (Product $product): array => [
                        'title' => $product->name,
                        'subtitle' => str_replace('_', ' ', (string) $product->type),
                        'url' => route('admin.products.edit', $product),
                    ])->all(),
                ];
            }
        }

        return response()->json(['groups' => $groups]);
    }
}
