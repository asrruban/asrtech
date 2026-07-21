<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Subscription;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SubscriptionRenewedMail extends Mailable
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Order $renewalOrder,
    ) {}

    public function envelope(): Envelope
    {
        $this->subscription->loadMissing('product');

        return new Envelope(
            subject: $this->template()['subject'] ?? __(':product subscription renewed', [
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
                with: ['body' => $template['html'], 'badge' => __('Subscription renewed')],
            );
        }

        return new Content(view: 'mail.subscription-renewed');
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->subscription->loadMissing(['user', 'product']);
        $this->renewalOrder->loadMissing('invoice');
        $invoice = $this->renewalOrder->invoice;

        return app(EmailTemplateService::class)->render('subscription-renewed', [
            'client_name' => $this->subscription->user->name,
            'product_name' => $this->subscription->product->name,
            'subscription_amount' => $this->formattedAmount(),
            'billing_cycle' => Str::headline($this->subscription->billing_cycle->value),
            'next_renewal_date' => $this->subscription->current_period_end?->format('d M Y') ?? '',
            'order_number' => $this->renewalOrder->order_number,
            'invoice_number' => $invoice->invoice_number,
            'invoice_url' => route('account.invoice', $invoice),
            'subscriptions_url' => route('account.subscriptions'),
        ]);
    }

    private function formattedAmount(): string
    {
        return $this->subscription->currency.' '.number_format((float) $this->renewalOrder->amount, 2);
    }
}
