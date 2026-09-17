<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SubscriptionDunningReminderMail extends Mailable implements ShouldQueue
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $attempt,
        public bool $isFinal,
        public ?int $daysUntilSuspension = null,
    ) {}

    public function envelope(): Envelope
    {
        $this->subscription->loadMissing('product');

        return new Envelope(
            subject: $this->template()['subject'] ?? ($this->isFinal
                ? __('Final notice: update your payment method for :product', [
                    'product' => $this->subscription->product->name,
                ])
                : __('Reminder: payment still outstanding for :product', [
                    'product' => $this->subscription->product->name,
                ])),
            bcc: $this->configuredBcc(),
        );
    }

    public function content(): Content
    {
        $template = $this->template();

        if ($template !== null) {
            return new Content(
                view: 'mail.template',
                with: ['body' => $template['html'], 'badge' => __('Payment reminder')],
            );
        }

        return new Content(view: 'mail.subscription-dunning-reminder');
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->subscription->loadMissing(['user', 'product']);

        return app(EmailTemplateService::class)->render('subscription-dunning-reminder', [
            'client_name' => $this->subscription->user->name,
            'product_name' => $this->subscription->product->name,
            'subscription_amount' => $this->subscription->currency.' '.number_format((float) $this->subscription->amount, 2),
            'billing_cycle' => Str::headline($this->subscription->billing_cycle->value),
            'attempt_number' => (string) $this->attempt,
            'days_until_suspension' => $this->daysUntilSuspension !== null ? (string) $this->daysUntilSuspension : '',
            'payment_method_url' => route('account.subscriptions'),
        ]);
    }
}
