<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';

    protected $description = 'Email reminders for unpaid invoices approaching or past their due date';

    public function handle(): int
    {
        if (! config('asrtech.invoice.reminders_enabled', false)) {
            $this->info('Invoice reminders are disabled in General Configuration.');

            return self::SUCCESS;
        }

        $days = max(1, (int) config('asrtech.invoice.reminder_days', 7));

        $invoices = Invoice::query()
            ->where('status', InvoiceStatus::Issued)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays($days))
            ->whereNull('last_reminder_at')
            ->with('order.user')
            ->get();

        foreach ($invoices as $invoice) {
            Mail::to($invoice->order->user->email)->send(new InvoiceMail($invoice));
            $invoice->forceFill(['last_reminder_at' => now()])->save();
        }

        $this->info("Sent {$invoices->count()} invoice reminder(s).");

        return self::SUCCESS;
    }
}
