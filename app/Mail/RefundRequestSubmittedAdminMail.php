<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestSubmittedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('New refund request :number', ['number' => $this->refundRequest->request_number]));
    }

    public function content(): Content
    {
        $this->refundRequest->loadMissing(['user', 'invoice']);

        return new Content(view: 'mail.refund-request-admin');
    }
}
