<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Jobs\SendInquiryNotification;
use App\Mail\InquiryMail;
use App\Models\Admin;
use App\Models\InquiryNotificationDelivery;
use App\Models\ProjectInquiry;
use App\Services\InquiryNotificationService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InquiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        Queue::fake();
    }

    public function test_notifications_are_off_until_the_owner_configures_and_verifies_delivery(): void
    {
        $this->post('/contact', $this->inquiry())->assertRedirect('/contact');
        $this->assertDatabaseCount('project_inquiries', 1);
        $this->assertDatabaseCount('inquiry_notification_deliveries', 0);
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_notification_settings_require_permission_and_real_verified_mail_configuration(): void
    {
        $support = $this->admin(AdminRole::Support);
        $this->actingAs($support, 'admin')->get('/admin/settings/inquiry-notifications')->assertForbidden();
        $this->actingAs($support, 'admin')->put('/admin/settings/inquiry-notifications', $this->settings())->assertForbidden();
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin/settings/inquiry-notifications')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Configuration/Settings/InquiryNotifications')->where('configuration.enabled', false));
        $this->put('/admin/settings/inquiry-notifications', $this->settings())->assertSessionHasErrors('enabled');
        $this->configureTransport();
        $this->put('/admin/settings/inquiry-notifications', [...$this->settings(), 'verified' => false])->assertSessionHasErrors('verified');
        $this->put('/admin/settings/inquiry-notifications', [...$this->settings(), 'recipient' => 'hello@example.com'])->assertSessionHasErrors('recipient');
        $this->put('/admin/settings/inquiry-notifications', $this->settings())->assertRedirect('/admin/settings/inquiry-notifications')->assertSessionHasNoErrors();
        $this->assertTrue(app(InquiryNotificationService::class)->configuration()['ready']);
        Mail::assertNothingSent();
    }

    public function test_log_transport_cannot_be_enabled_even_with_confirmed_real_addresses(): void
    {
        $this->configureTransport();
        config(['mail.default' => 'log']);
        $this->actingAs($this->admin(), 'admin')->put('/admin/settings/inquiry-notifications', $this->settings())->assertSessionHasErrors('enabled');
    }

    public function test_enabled_contact_submission_saves_two_durable_jobs_without_sending_in_the_request(): void
    {
        $this->enable();
        $this->post('/contact', $this->inquiry())->assertRedirect('/contact')->assertSessionHas('inquiry_received', true);
        $inquiry = ProjectInquiry::query()->firstOrFail();
        $this->assertDatabaseCount('inquiry_notification_deliveries', 2);
        $this->assertDatabaseHas('inquiry_notification_deliveries', ['project_inquiry_id' => $inquiry->id, 'kind' => 'admin', 'recipient' => 'owner@asrtech.bd', 'status' => 'pending']);
        $this->assertDatabaseHas('inquiry_notification_deliveries', ['project_inquiry_id' => $inquiry->id, 'kind' => 'acknowledgement', 'recipient' => $inquiry->email]);
        Queue::assertPushed(SendInquiryNotification::class, 2);
        Mail::assertNothingSent();
        app(InquiryNotificationService::class)->record($inquiry);
        $this->assertDatabaseCount('inquiry_notification_deliveries', 2);
    }

    public function test_acknowledgements_are_optional(): void
    {
        $this->enable(false);
        $this->post('/contact', $this->inquiry())->assertRedirect('/contact');
        $this->assertDatabaseCount('inquiry_notification_deliveries', 1);
        $this->assertDatabaseHas('inquiry_notification_deliveries', ['kind' => 'admin']);
    }

    public function test_notification_storage_failure_does_not_lose_the_inquiry_or_claim_submission_failed(): void
    {
        $this->enable();
        InquiryNotificationDelivery::creating(fn () => throw new \RuntimeException('Outbox unavailable'));
        try {
            $this->post('/contact', $this->inquiry())->assertRedirect('/contact')->assertSessionHas('inquiry_received', true);
            $this->assertDatabaseCount('project_inquiries', 1);
        } finally {
            InquiryNotificationDelivery::flushEventListeners();
        }
    }

    public function test_queue_outage_keeps_the_inquiry_and_outbox_available_for_recovery(): void
    {
        $this->enable();
        Queue::shouldReceive('connection')->twice()->with('database')->andThrow(new \RuntimeException('Queue unavailable'));
        $this->post('/contact', $this->inquiry())->assertRedirect('/contact')->assertSessionHas('inquiry_received', true);
        $this->assertDatabaseCount('project_inquiries', 1);
        $this->assertDatabaseCount('inquiry_notification_deliveries', 2);
        foreach (InquiryNotificationDelivery::all() as $delivery) {
            $this->assertNull($delivery->queued_at);
            $this->assertSame('pending', $delivery->status);
            $this->assertStringContainsString('Queue unavailable', $delivery->last_error);
        }
        Mail::assertNothingSent();
    }

    public function test_job_sends_each_delivery_once_and_receipt_does_not_echo_untrusted_content(): void
    {
        $this->enable();
        $this->post('/contact', $this->inquiry());
        $notifications = app(InquiryNotificationService::class);
        foreach (InquiryNotificationDelivery::all() as $delivery) {
            (new SendInquiryNotification($delivery->id))->handle($notifications);
            (new SendInquiryNotification($delivery->id))->handle($notifications);
            $this->assertSame('sent', $delivery->fresh()->status);
            $this->assertSame(1, $delivery->fresh()->attempts);
        }
        Mail::assertSent(InquiryMail::class, 2);
        $ack = InquiryNotificationDelivery::query()->where('kind', 'acknowledgement')->firstOrFail();
        $rendered = (new InquiryMail($ack))->render();
        $this->assertStringNotContainsString('untrusted-customer-message', $rendered);
        $this->assertStringContainsString('Reference: #'.$ack->project_inquiry_id, $rendered);
        $admin = InquiryNotificationDelivery::query()->where('kind', 'admin')->firstOrFail();
        $this->assertStringContainsString('&lt;script&gt;', (new InquiryMail($admin))->render());
        $this->assertStringNotContainsString('<script>', (new InquiryMail($admin))->render());
        $this->assertSame((new InquiryMail($ack))->headers()->messageId, (new InquiryMail($ack))->headers()->messageId);
    }

    public function test_changed_sender_or_disabled_notifications_hold_pending_deliveries(): void
    {
        $this->enable();
        $this->post('/contact', $this->inquiry());
        app(SettingService::class)->put('mail_from_address', 'changed@asrtech.bd');
        $delivery = InquiryNotificationDelivery::query()->firstOrFail();
        (new SendInquiryNotification($delivery->id))->handle(app(InquiryNotificationService::class));
        $this->assertSame('held', $delivery->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_email_failure_is_recorded_for_retry_without_changing_the_inquiry(): void
    {
        $this->enable();
        $this->post('/contact', $this->inquiry());
        $delivery = InquiryNotificationDelivery::query()->firstOrFail();
        Mail::shouldReceive('to')->once()->with($delivery->recipient)->andThrow(new \RuntimeException('SMTP secret must not be exposed'));
        try {
            (new SendInquiryNotification($delivery->id))->handle(app(InquiryNotificationService::class));
            $this->fail('Expected delivery failure.');
        } catch (\RuntimeException) {
            $this->assertSame('failed', $delivery->fresh()->status);
            $this->assertSame(1, $delivery->fresh()->attempts);
            $this->assertStringNotContainsString('secret', $delivery->fresh()->last_error);
            $this->assertDatabaseCount('project_inquiries', 1);
        }
    }

    public function test_dispatcher_recovers_unqueued_deliveries_but_does_not_repeat_sent_or_exhausted_work(): void
    {
        $this->enable(false);
        $this->post('/contact', $this->inquiry());
        $delivery = InquiryNotificationDelivery::query()->firstOrFail();
        $delivery->update(['queued_at' => null]);
        Queue::fake();
        $this->artisan('inquiry-notifications:dispatch')->expectsOutput('Queued 1 inquiry notifications.')->assertSuccessful();
        Queue::assertPushed(SendInquiryNotification::class, 1);
        $delivery->update(['queued_at' => null, 'attempts' => 5, 'status' => 'failed']);
        Queue::fake();
        $this->artisan('inquiry-notifications:dispatch')->expectsOutput('Queued 0 inquiry notifications.')->assertSuccessful();
        Queue::assertNothingPushed();
    }

    public function test_authorized_admin_can_retry_a_failed_delivery_but_not_resend_a_sent_receipt(): void
    {
        $this->enable(false);
        $this->post('/contact', $this->inquiry());
        $delivery = InquiryNotificationDelivery::query()->firstOrFail();
        $delivery->update(['status' => 'failed', 'attempts' => 5]);
        Queue::fake();
        $this->actingAs($this->admin(), 'admin')->from('/admin/settings/inquiry-notifications')
            ->post('/admin/settings/inquiry-notifications/'.$delivery->id.'/retry')->assertRedirect('/admin/settings/inquiry-notifications');
        $this->assertSame(0, $delivery->fresh()->attempts);
        Queue::assertPushed(SendInquiryNotification::class, 1);
        $delivery->update(['sent_at' => now(), 'status' => 'sent']);
        Queue::fake();
        $this->post('/admin/settings/inquiry-notifications/'.$delivery->id.'/retry')->assertRedirect();
        Queue::assertNothingPushed();
    }

    private function enable(bool $acknowledgements = true): void
    {
        $this->configureTransport();
        foreach ([
            'inquiry_notifications_enabled' => '1',
            'inquiry_acknowledgements_enabled' => $acknowledgements ? '1' : '0',
            'inquiry_notification_recipient' => 'owner@asrtech.bd',
            'inquiry_notification_verified_recipient' => 'owner@asrtech.bd',
            'inquiry_notification_verified_sender' => 'notifications@asrtech.bd',
        ] as $key => $value) {
            app(SettingService::class)->put($key, $value);
        }
    }

    private function configureTransport(): void
    {
        // Addresses are isolated test data, never seeded or used to send mail.
        config(['mail.default' => 'smtp']);
        app(SettingService::class)->put('mail_from_address', 'notifications@asrtech.bd');
    }

    private function settings(): array
    {
        return ['enabled' => true, 'acknowledgements' => true, 'recipient' => 'owner@asrtech.bd', 'verified' => true];
    }

    private function inquiry(): array
    {
        return ['name' => 'Test customer', 'email' => 'client@example.test', 'service' => 'web-development', 'message' => 'untrusted-customer-message <script> with project requirements'];
    }

    private function admin(AdminRole $role = AdminRole::SuperAdmin): Admin
    {
        return Admin::query()->create(['name' => 'Test admin', 'email' => uniqid().'@example.test', 'password' => 'password', 'role' => $role]);
    }
}
