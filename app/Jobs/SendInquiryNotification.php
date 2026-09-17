<?php

namespace App\Jobs;

use App\Mail\InquiryMail;
use App\Models\InquiryNotificationDelivery;
use App\Services\InquiryNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInquiryNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public readonly int $deliveryId) {}

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('inquiry-delivery:'.$this->deliveryId))->releaseAfter(70)->expireAfter(120)];
    }

    public function handle(InquiryNotificationService $notifications): void
    {
        $delivery = InquiryNotificationDelivery::query()->with('inquiry')->find($this->deliveryId);
        if ($delivery === null || $delivery->sent_at !== null || $delivery->attempts >= 5) {
            return;
        }

        $configuration = $notifications->configuration();
        if (! $configuration['enabled'] || ! $configuration['ready']
            || ($delivery->kind === 'acknowledgement' && ! $configuration['acknowledgements'])
            || $delivery->sender !== $configuration['sender']
            || ($delivery->kind === 'admin' && $delivery->recipient !== $configuration['recipient'])) {
            $delivery->update(['status' => 'held', 'last_error' => 'Delivery paused because notification settings changed. Review the settings before retrying.']);

            return;
        }

        $delivery->update(['status' => 'processing', 'attempts' => $delivery->attempts + 1]);

        try {
            Mail::to($delivery->recipient)->send(new InquiryMail($delivery));
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'last_error' => 'Email delivery failed. Check the mail configuration and application logs, then retry.']);

            throw $exception;
        }
    }
}
