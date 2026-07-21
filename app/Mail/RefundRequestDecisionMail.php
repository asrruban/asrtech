<?php

namespace App\Mail;

use App\Models\RefundRequest;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestDecisionMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template()['subject'] ?? __('Refund request :number :status', [
                'number' => $this->refundRequest->request_number,
                'status' => $this->refundRequest->status->value,
            ]),
            bcc: $this->configuredBcc(),
        );
    }

    public function content(): Content
    {
        $template = $this->template();

        return $template === null
            ? new Content(view: 'mail.refund-request-decision')
            : new Content(view: 'mail.template', with: ['body' => $template['html'], 'badge' => __('Refund request update')]);
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->refundRequest->loadMissing(['user', 'invoice']);

        return app(EmailTemplateService::class)->render('refund-request-decision', [
            'client_name' => $this->refundRequest->user->name,
            'request_number' => $this->refundRequest->request_number,
            'request_amount' => $this->refundRequest->currency.' '.number_format((float) $this->refundRequest->amount, 2),
            'request_status' => $this->refundRequest->status->value,
            'decision_note' => $this->refundRequest->admin_note ?: __('Contact our billing team if you have any questions.'),
            'invoice_number' => $this->refundRequest->invoice->invoice_number,
            'invoice_url' => route('account.invoice', $this->refundRequest->invoice_id),
        ]);
    }
}
