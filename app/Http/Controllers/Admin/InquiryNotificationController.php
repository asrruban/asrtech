<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InquiryNotificationDelivery;
use App\Services\InquiryNotificationService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InquiryNotificationController extends Controller
{
    public function edit(InquiryNotificationService $notifications): Response
    {
        return Inertia::render('Admin/Configuration/Settings/InquiryNotifications', [
            'configuration' => $notifications->configuration(),
            'deliveries' => InquiryNotificationDelivery::query()->latest()->paginate(20)
                ->through(fn (InquiryNotificationDelivery $delivery) => [
                    'id' => $delivery->id,
                    'inquiry_id' => $delivery->project_inquiry_id,
                    'kind' => $delivery->kind,
                    'recipient' => $delivery->recipient,
                    'status' => $delivery->status,
                    'attempts' => $delivery->attempts,
                    'sent_at' => $delivery->sent_at?->toIso8601String(),
                    'last_error' => $delivery->last_error,
                ]),
        ]);
    }

    public function update(Request $request, SettingService $settings, InquiryNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'acknowledgements' => ['required', 'boolean'],
            'recipient' => [Rule::requiredIf($request->boolean('enabled')), 'nullable', 'email:rfc', 'max:255'],
            'verified' => [Rule::requiredIf($request->boolean('enabled')), 'boolean'],
        ]);
        $configuration = $notifications->configuration();
        if ($data['enabled']) {
            if (! $request->boolean('verified')) {
                throw ValidationException::withMessages(['verified' => 'Confirm your sender verification and inbox ownership before enabling notifications.']);
            }
            if (! InquiryNotificationService::isDeliverableAddress($configuration['sender'])) {
                throw ValidationException::withMessages(['enabled' => 'Configure a real sending address in General Configuration first.']);
            }
            if (! InquiryNotificationService::isDeliverableAddress((string) ($data['recipient'] ?? ''))) {
                throw ValidationException::withMessages(['recipient' => 'Use a real inbox you control. Placeholder addresses cannot receive notifications.']);
            }
            if (! in_array($configuration['transport'], ['smtp', 'sendmail', 'ses', 'ses-v2', 'mailgun', 'postmark', 'resend'], true)) {
                throw ValidationException::withMessages(['enabled' => 'Configure a delivery mail transport before enabling notifications.']);
            }
        }

        DB::transaction(function () use ($settings, $data, $configuration): void {
            $settings->put('inquiry_notifications_enabled', $data['enabled'] ? '1' : '0');
            $settings->put('inquiry_acknowledgements_enabled', $data['acknowledgements'] ? '1' : '0');
            $settings->put('inquiry_notification_recipient', $data['recipient'] ?? null);
            if ($data['enabled']) {
                $settings->put('inquiry_notification_verified_sender', $configuration['sender']);
                $settings->put('inquiry_notification_verified_recipient', $data['recipient']);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquiry notification settings saved.']);

        return redirect()->route('admin.settings.inquiry-notifications.edit');
    }

    public function retry(InquiryNotificationDelivery $delivery, InquiryNotificationService $notifications): RedirectResponse
    {
        $configuration = $notifications->configuration();
        if (! $configuration['enabled'] || ! $configuration['ready']) {
            throw ValidationException::withMessages(['delivery' => 'Enable and verify notification settings before retrying.']);
        }
        if ($delivery->sent_at !== null) {
            return back();
        }
        if (! in_array($delivery->status, ['failed', 'held'], true)) {
            throw ValidationException::withMessages(['delivery' => 'This delivery is already waiting for a worker.']);
        }
        if ($delivery->kind === 'acknowledgement' && ! $configuration['acknowledgements']) {
            throw ValidationException::withMessages(['delivery' => 'Enable customer acknowledgements before retrying this delivery.']);
        }

        $delivery->update([
            'status' => 'pending', 'attempts' => 0, 'last_error' => null,
            'sender' => $configuration['sender'],
            'recipient' => $delivery->kind === 'admin' ? $configuration['recipient'] : $delivery->recipient,
        ]);
        $queued = $notifications->enqueue($delivery);
        Inertia::flash('toast', [
            'type' => $queued ? 'success' : 'error',
            'message' => $queued ? 'Delivery queued for retry.' : 'Queue unavailable. The delivery remains saved for the next dispatcher run.',
        ]);

        return back();
    }
}
