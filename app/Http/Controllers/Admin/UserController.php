<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Admin/Users/Index', [
            'filters' => ['search' => $search],
            'users' => User::query()
                ->withCount(['orders', 'licenses'])
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults()],
            'verified' => ['required', 'boolean'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if ($request->boolean('verified')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User :name created.', ['name' => $user->name])]);

        return redirect()->route('admin.users.show', $user);
    }

    public function show(User $user, string $tab = 'summary'): Response
    {
        $user->loadCount(['orders', 'licenses']);

        $invoices = Invoice::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->with(['order:id,order_number,currency,amount,setup_fee,tax_amount,payment_method,paid_at,product_id', 'order.product:id,name'])
            ->latest('id')
            ->get();

        $totalFor = fn ($invoice) => $invoice->order->totalAmount();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'activeTab' => $tab,
            'invoices' => $invoices,
            'billing' => [
                'paid_count' => $invoices->where('status', InvoiceStatus::Paid)->count(),
                'paid_total' => round($invoices->where('status', InvoiceStatus::Paid)->sum($totalFor), 2),
                'unpaid_count' => $invoices->where('status', InvoiceStatus::Issued)->count(),
                'unpaid_total' => round($invoices->where('status', InvoiceStatus::Issued)->sum($totalFor), 2),
                'void_count' => $invoices->where('status', InvoiceStatus::Void)->count(),
                'gross_revenue' => round((float) $user->orders()->where('status', OrderStatus::Paid)->get()->sum(fn (Order $order): float => $order->totalAmount()), 2),
            ],
            'orders' => $user->orders()
                ->with(['product:id,name,slug', 'license:id,order_id,license_key', 'invoice:id,order_id,invoice_number,status'])
                ->latest()
                ->get(),
            'licenses' => $user->licenses()
                ->with(['product:id,name,slug', 'order:id,order_number,currency,amount,setup_fee,billing_cycle'])
                ->latest()
                ->get(),
            'paymentMethods' => $user->paymentMethods()
                ->latest()
                ->get(['id', 'gateway', 'type', 'card_brand', 'card_last_four', 'card_expiry_month', 'card_expiry_year', 'name_on_card', 'created_at']),
            'products' => Product::query()
                ->where('status', true)
                ->with(['prices' => fn ($query) => $query->orderByDesc('featured')->orderBy('price')])
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2', 'uppercase'],
            'password' => ['nullable', 'string', Password::defaults()],
            'verified' => ['required', 'boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address_1' => $data['address_1'] ?? null,
            'address_2' => $data['address_2'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'country' => $data['country'] ?? null,
            ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
        ]);

        $user->forceFill([
            'email_verified_at' => $request->boolean('verified')
                ? ($user->email_verified_at ?? now())
                : null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated for :name.', ['name' => $user->name])]);

        return redirect()->route('admin.users.show', [$user, 'profile']);
    }

    public function updateNotes(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user->update(['admin_notes' => $data['admin_notes']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notes saved.')]);

        return redirect()->route('admin.users.show', $user);
    }
}
