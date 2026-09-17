<?php

namespace App\Mail;

use App\Models\InquiryNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class InquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly InquiryNotificationDelivery $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->delivery->sender, (string) config('asrtech.business.name', 'ASR Tech')),
            subject: $this->delivery->kind === 'admin' ? 'New project inquiry | ASR Tech' : 'Your inquiry was received | ASR Tech',
        );
    }

    public function headers(): Headers
    {
        $domain = substr(strrchr($this->delivery->sender, '@') ?: '@asrtech.bd', 1);

        return new Headers(messageId: 'asr-inquiry-'.$this->delivery->id.'@'.$domain);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.inquiry', with: [
            'inquiry' => $this->delivery->inquiry,
            'isAcknowledgement' => $this->delivery->kind === 'acknowledgement',
            'inboxUrl' => route('admin.inquiries.index'),
        ]);
    }
}
