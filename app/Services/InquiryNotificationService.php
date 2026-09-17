<?php

namespace App\Services;

use App\Jobs\SendInquiryNotification;
use App\Models\InquiryNotificationDelivery;
use App\Models\ProjectInquiry;
use Illuminate\Support\Facades\DB;
use Throwable;

class InquiryNotificationService
{
    public function __construct(private readonly SettingService $settings) {}

    /** @return array{enabled: bool, acknowledgements: bool, recipient: string, sender: string, verified_sender: string, verified_recipient: string, mailer: string, transport: string, ready: bool, issue: string|null} */
    public function configuration(): array
    {
        $sender = (string) $this->settings->get('mail_from_address', config('mail.from.address'));
        $recipient = (string) $this->settings->get('inquiry_notification_recipient', '');
        $verifiedSender = (string) $this->settings->get('inquiry_notification_verified_sender', '');
        $verifiedRecipient = (string) $this->settings->get('inquiry_notification_verified_recipient', '');
        $mailer = (string) config('mail.default');
        $transport = (string) config('mail.mailers.'.$mailer.'.transport', $mailer);
        $issue = null;

        if (! self::isDeliverableAddress($sender) || ! self::isDeliverableAddress($recipient)) {
            $issue = 'Set a real sending address in General Configuration and a real inbox below.';
        } elseif ($verifiedSender !== $sender || $verifiedRecipient !== $recipient) {
            $issue = 'Confirm that the sending address is verified with your mail provider and that you control the receiving inbox.';
        } elseif (! in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'mailgun', 'postmark', 'resend'], true)) {
            $issue = 'Configure a delivery mail transport in General Configuration or the environment. The current transport does not guarantee email delivery.';
        }

        return [
            'enabled' => $this->settings->get('inquiry_notifications_enabled', '0') === '1',
            'acknowledgements' => $this->settings->get('inquiry_acknowledgements_enabled', '0') === '1',
            'recipient' => $recipient,
            'sender' => $sender,
            'verified_sender' => $verifiedSender,
            'verified_recipient' => $verifiedRecipient,
            'mailer' => $mailer,
            'transport' => $transport,
            'ready' => $issue === null,
            'issue' => $issue,
        ];
    }

    public static function isDeliverableAddress(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return str_contains($domain, '.')
            && ! preg_match('/(^|\.)(example\.(com|net|org)|localhost|test|invalid|example)$/', $domain);
    }

    public function record(ProjectInquiry $inquiry): void
    {
        $configuration = $this->configuration();
        if (! $configuration['enabled'] || ! $configuration['ready']) {
            return;
        }

        $deliveries = DB::transaction(function () use ($inquiry, $configuration): array {
            $deliveries = [InquiryNotificationDelivery::query()->firstOrCreate([
                'project_inquiry_id' => $inquiry->id,
                'kind' => 'admin',
            ], ['recipient' => $configuration['recipient'], 'sender' => $configuration['sender']])];

            // Avoid sending user-supplied text to an unverified form address.
            // The receipt contains only a fixed acknowledgement and reference.
            if ($configuration['acknowledgements']) {
                $deliveries[] = InquiryNotificationDelivery::query()->firstOrCreate([
                    'project_inquiry_id' => $inquiry->id,
                    'kind' => 'acknowledgement',
                ], ['recipient' => $inquiry->email, 'sender' => $configuration['sender']]);
            }

            return $deliveries;
        });

        foreach ($deliveries as $delivery) {
            $this->enqueue($delivery);
        }
    }

    public function enqueue(InquiryNotificationDelivery $delivery): bool
    {
        if ($delivery->sent_at !== null || $delivery->attempts >= 5) {
            return false;
        }

        try {
            SendInquiryNotification::dispatch($delivery->id)
                ->onConnection((string) config('inquiry_notifications.queue_connection', 'database'));
            $delivery->update(['queued_at' => now()]);

            return true;
        } catch (Throwable $exception) {
            $delivery->update(['last_error' => 'Queue unavailable. The dispatcher will retry this delivery.']);
            report($exception);

            return false;
        }
    }
}
