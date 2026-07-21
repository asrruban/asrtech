<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template()['subject'] ?? __(':code is your :app verification code', [
                'code' => $this->code,
                'app' => (string) config('app.name'),
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
                with: ['body' => $template['html'], 'badge' => __('Verify email')],
            );
        }

        return new Content(
            view: 'mail.email-otp',
            with: [
                'ttlMinutes' => EmailOtpService::ttlMinutes(),
            ],
        );
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        return app(EmailTemplateService::class)->render('email-otp', [
            'client_name' => $this->user->name,
            'otp_code' => $this->code,
            'otp_expiry_minutes' => (string) EmailOtpService::ttlMinutes(),
        ]);
    }
}
