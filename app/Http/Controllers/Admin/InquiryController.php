<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProjectInquiry;
use App\Models\Quote;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(ProjectInquiry::STATUSES)],
            'follow_up' => ['nullable', Rule::in(['due'])],
        ]);

        return Inertia::render('Admin/Support/Inquiries/Index', [
            'inquiries' => ProjectInquiry::query()->with(['assignee:id,name', 'user:id,name,email', 'quote:id,quote_number,status'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when(($filters['follow_up'] ?? null) === 'due', fn ($query) => $query->whereDate('follow_up_at', '<=', today())->whereNotIn('status', ['won', 'closed']))
                ->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statuses' => ProjectInquiry::STATUSES,
            'serviceNames' => [...config('asrtech.services', []), 'not-sure' => 'Help choosing a service'],
        ]);
    }

    public function show(Request $request, ProjectInquiry $inquiry): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $inquiry->load(['user:id,name,email', 'assignee:id,name', 'quote:id,quote_number,status', 'project:id,project_inquiry_id,title']);

        return Inertia::render('Admin/Support/Inquiries/Show', [
            'inquiry' => $inquiry,
            'statuses' => ProjectInquiry::STATUSES,
            'serviceNames' => [...config('asrtech.services', []), 'not-sure' => 'Help choosing a service'],
            'clients' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'admins' => Admin::query()->orderBy('name')->get()->filter(fn (Admin $item): bool => $item->hasPermission('support.manage'))->values()->map->only(['id', 'name']),
            'canManageBilling' => $admin->hasPermission('billing.manage'),
            'products' => $admin->hasPermission('billing.manage') ? Product::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'price']) : [],
            'quotes' => $admin->hasPermission('billing.manage') && $inquiry->user_id
                ? Quote::query()->where('user_id', $inquiry->user_id)->latest()->get(['id', 'quote_number', 'status']) : [],
            'currency' => (string) config('asrtech.currency', 'USD'),
        ]);
    }

    public function update(Request $request, ProjectInquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(ProjectInquiry::STATUSES)],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'assigned_admin_id' => ['sometimes', 'nullable', 'integer', 'exists:admins,id'],
            'internal_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'follow_up_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);
        DB::transaction(function () use ($request, $inquiry, $data): void {
            $inquiry = ProjectInquiry::query()->lockForUpdate()->findOrFail($inquiry->id);
            if (array_key_exists('user_id', $data) && (int) $data['user_id'] !== (int) $inquiry->user_id && ($inquiry->quote_id || $inquiry->project()->exists())) {
                throw ValidationException::withMessages(['user_id' => 'The linked client cannot change after a quote or project is created.']);
            }
            if (! empty($data['assigned_admin_id']) && ! Admin::query()->whereKey($data['assigned_admin_id'])->firstOrFail()->hasPermission('support.manage')) {
                throw ValidationException::withMessages(['assigned_admin_id' => 'Choose an administrator who can manage support.']);
            }
            $before = $inquiry->only(['status', 'user_id', 'assigned_admin_id', 'follow_up_at']);
            $inquiry->update($data);
            /** @var Admin $admin */
            $admin = $request->user('admin');
            app(AdminAuditService::class)->record($admin, 'inquiry.updated', 'Updated inquiry follow-up and ownership.', $inquiry, ['before' => $before, 'after' => $inquiry->only(array_keys($before)), 'notes_updated' => array_key_exists('internal_notes', $data)]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquiry saved.']);

        return back();
    }

    public function quote(Request $request, ProjectInquiry $inquiry, QuoteService $quotes): RedirectResponse
    {
        $data = $request->validate([
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'product_id' => ['required_without:quote_id', 'nullable', 'integer', Rule::exists('products', 'id')->where('status', true)],
            'unit_price' => ['required_without:quote_id', 'nullable', 'numeric', 'min:0', 'max:999999999'],
            'quantity' => ['required_without:quote_id', 'nullable', 'integer', 'min:1', 'max:1000'],
            'billing_cycle' => ['required_without:quote_id', 'nullable', Rule::in(['one_time', 'monthly', 'yearly'])],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        DB::transaction(function () use ($request, $inquiry, $quotes, $data): void {
            $inquiry = ProjectInquiry::query()->lockForUpdate()->findOrFail($inquiry->id);
            if ($inquiry->quote_id) {
                return;
            }
            if (! $inquiry->user_id) {
                throw ValidationException::withMessages(['quote' => 'Select and save an existing client before linking a quote.']);
            }
            if (! empty($data['quote_id'])) {
                $quote = Quote::query()->where('user_id', $inquiry->user_id)->lockForUpdate()->whereKey($data['quote_id'])->first();
                if (! $quote || ProjectInquiry::query()->where('quote_id', $quote->id)->exists()) {
                    throw ValidationException::withMessages(['quote_id' => 'Choose an unlinked quote for this client.']);
                }
            } else {
                $product = Product::query()->whereKey($data['product_id'])->firstOrFail();
                $quote = Quote::query()->create([
                    'quote_number' => $quotes->generateQuoteNumber(), 'user_id' => $inquiry->user_id,
                    'status' => QuoteStatus::Draft, 'currency' => (string) config('asrtech.currency', 'USD'),
                    'valid_until' => $data['valid_until'] ?? null,
                ]);
                $quotes->syncItems($quote, [[
                    'product_id' => $product->id, 'product_name' => $product->name,
                    'quantity' => (int) $data['quantity'], 'unit_price' => (float) $data['unit_price'], 'billing_cycle' => $data['billing_cycle'],
                ]], (float) ($data['tax_rate'] ?? 0));
            }
            $inquiry->update(['quote_id' => $quote->id, 'status' => 'quoted']);
            /** @var Admin $admin */
            $admin = $request->user('admin');
            app(AdminAuditService::class)->record($admin, 'inquiry.quote_linked', 'Linked inquiry to quote.', $inquiry, ['quote_id' => $quote->id, 'user_id' => $inquiry->user_id]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quote linked. Review and send it from Quotes when ready.']);

        return back();
    }
}
