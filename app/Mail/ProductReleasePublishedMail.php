<?php

namespace App\Mail;

use App\Models\License;
use App\Models\ProductRelease;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductReleasePublishedMail extends Mailable implements ShouldQueue
{
    use Concerns\BccFromConfiguration, Queueable, SerializesModels;

    public function __construct(
        public ProductRelease $release,
        public User $user,
        public License $license,
    ) {}

    public function envelope(): Envelope
    {
        $this->release->loadMissing('product');

        return new Envelope(
            subject: $this->template()['subject'] ?? __(':product :version is now available', [
                'product' => $this->release->product->name,
                'version' => $this->release->version,
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
                with: [
                    'body' => $template['html'],
                    'badge' => __('Product update'),
                ],
            );
        }

        return new Content(view: 'mail.product-release-published');
    }

    /** @return array{subject: string, html: string}|null */
    private function template(): ?array
    {
        $this->release->loadMissing('product');

        return app(EmailTemplateService::class)->render('product-release-published', [
            'client_name' => $this->user->name,
            'product_name' => $this->release->product->name,
            'version' => $this->release->version,
            'release_title' => $this->release->title ?: __('Product update'),
            'release_notes' => $this->release->release_notes ?: __('See the client area for release details.'),
            'release_date' => $this->release->released_at->format('d M Y'),
            'downloads_url' => route('account.product', $this->license),
        ]);
    }
}
