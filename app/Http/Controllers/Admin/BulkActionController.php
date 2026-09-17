<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Throwable;

class BulkActionController extends Controller
{
    /**
     * Bulk delete users. Users with orders are skipped to protect
     * financial records.
     */
    public function destroyUsers(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $deletable = User::query()
            ->whereIn('id', $data['ids'])
            ->whereDoesntHave('orders')
            ->get();

        $deleted = 0;

        foreach ($deletable as $user) {
            $user->delete();
            $deleted++;
        }

        $skipped = count($data['ids']) - $deleted;

        Inertia::flash('toast', [
            'type' => $skipped > 0 ? 'warning' : 'success',
            'message' => $skipped > 0
                ? __('Deleted :deleted user(s); skipped :skipped with order history.', ['deleted' => $deleted, 'skipped' => $skipped])
                : __('Deleted :deleted user(s).', ['deleted' => $deleted]),
        ]);

        return redirect()->route('admin.users.index');
    }

    /** Bulk (re)send invoice reminder emails for issued invoices. */
    public function remindInvoices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:invoices,id'],
        ]);

        $sent = 0;
        $failed = 0;

        Invoice::query()
            ->whereIn('id', $data['ids'])
            ->where('status', InvoiceStatus::Issued)
            ->with('order.user')
            ->get()
            ->each(function (Invoice $invoice) use (&$sent, &$failed): void {
                try {
                    Mail::to($invoice->order->user->email)->send(new InvoiceMail($invoice));
                    $invoice->forceFill(['last_reminder_at' => now()])->save();
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            });

        Inertia::flash('toast', [
            'type' => $failed > 0 ? 'warning' : 'success',
            'message' => __('Sent :sent invoice reminder(s).', ['sent' => $sent])
                .($failed > 0 ? __(' :failed failed.', ['failed' => $failed]) : ''),
        ]);

        return redirect()->route('admin.invoices.index');
    }
}
