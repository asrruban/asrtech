<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable implements ShouldQueue
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Quote :number from :company', [
                'number' => $this->quote->quote_number,
                'company' => config('asrtech.company_name', 'ASRTech'),
            ]),
            bcc: $this->configuredBcc(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.quote');
    }
}
