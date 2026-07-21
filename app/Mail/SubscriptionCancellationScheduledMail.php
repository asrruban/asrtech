<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancellationScheduledMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        $this->subscription->loadMissing('product');

        return new Envelope(
            subject: $this->template()['subject'] ?? __('Cancellation scheduled for :product', [
                'product' => $this->subscription->product->name,
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
                with: ['body' => $template['html'], 'badge' => __('Cancellation scheduled')],
            );
        }

        return new Content(view: 'mail.subscription-cancellation-scheduled');
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->subscription->loadMissing(['user', 'product']);

        return app(EmailTemplateService::class)->render('subscription-cancellation-scheduled', [
            'client_name' => $this->subscription->user->name,
            'product_name' => $this->subscription->product->name,
            'service_end_date' => $this->subscription->current_period_end?->format('d M Y') ?? '',
            'subscriptions_url' => route('account.subscriptions'),
        ]);
    }
}
