<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\EmailTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template()['subject'] ?? __('Invoice :number from :company', [
                'number' => $this->invoice->invoice_number,
                'company' => (string) config('asrtech.company_name', config('app.name')),
            ]),
            bcc: $this->configuredBcc(),
        );
    }

    public function content(): Content
    {
        $template = $this->template();

        if ($template !== null) {
            return new Content(
                view: 'mail.template',
                with: ['body' => $template['html'], 'badge' => __('Invoice')],
            );
        }

        return new Content(view: 'mail.invoice');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice->load(['order.user', 'order.product', 'order.productPrice', 'order.licenses', 'order.items']),
            'payTo' => config('asrtech.invoice.pay_to'),
            'footerNote' => config('asrtech.invoice.footer_note'),
        ]);

        return [
            Attachment::fromData(fn (): string => $pdf->output(), "{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $order = $this->invoice->order->loadMissing('items');
        $productName = $order->items->isNotEmpty()
            ? $order->items->pluck('product_name')->join(', ')
            : $order->product->name;

        return app(EmailTemplateService::class)->render('invoice-notification', [
            'client_name' => $order->user->name,
            'invoice_number' => (string) $this->invoice->invoice_number,
            'invoice_total' => $order->currency.' '.number_format($order->totalAmount(), 2),
            'invoice_due_date' => $this->invoice->due_at?->format('d M Y') ?? '',
            'invoice_status' => $this->invoice->status->value,
            'product_name' => $productName,
        ]);
    }
}
