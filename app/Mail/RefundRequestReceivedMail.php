<?php

namespace App\Mail;

use App\Models\RefundRequest;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestReceivedMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template()['subject'] ?? __('Refund request :number received', ['number' => $this->refundRequest->request_number]),
            bcc: $this->configuredBcc(),
        );
    }

    public function content(): Content
    {
        $template = $this->template();

        return $template === null
            ? new Content(view: 'mail.refund-request-received')
            : new Content(view: 'mail.template', with: ['body' => $template['html'], 'badge' => __('Refund request received')]);
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->refundRequest->loadMissing(['user', 'invoice']);

        return app(EmailTemplateService::class)->render('refund-request-received', [
            'client_name' => $this->refundRequest->user->name,
            'request_number' => $this->refundRequest->request_number,
            'request_amount' => $this->money(),
            'invoice_number' => $this->refundRequest->invoice->invoice_number,
            'invoice_url' => route('account.invoice', $this->refundRequest->invoice_id),
        ]);
    }

    private function money(): string
    {
        return $this->refundRequest->currency.' '.number_format((float) $this->refundRequest->amount, 2);
    }
}
