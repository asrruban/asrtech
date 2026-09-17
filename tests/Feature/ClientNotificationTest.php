<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ClientNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_database_notification_and_unread_count_is_shared(): void
    {
        $user = User::factory()->create();

        $user->notify(new ClientNotification(
            title: 'Payment failed',
            message: 'We could not renew your subscription.',
            url: '/client-area/subscriptions',
            level: 'danger',
        ));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)->get('/client-area/notifications');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Account/Notifications')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.title', 'Payment failed')
            ->where('notifications.data.0.read', false));
    }

    public function test_reading_a_notification_marks_it_read_and_redirects_to_its_url(): void
    {
        $user = User::factory()->create();
        $user->notify(new ClientNotification(
            title: 'Ticket reply',
            message: 'Support replied.',
            url: '/client-area/tickets',
        ));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->post("/client-area/notifications/{$notification->id}/read")
            ->assertRedirect('/client-area/tickets');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new ClientNotification(title: 'One', message: 'a'));
        $user->notify(new ClientNotification(title: 'Two', message: 'b'));

        $this->actingAs($user)
            ->post('/client-area/notifications/read-all')
            ->assertRedirect('/client-area/notifications');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_users_cannot_read_other_peoples_notifications(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $owner->notify(new ClientNotification(title: 'Secret', message: 'x'));
        $notification = $owner->notifications()->sole();

        $this->actingAs($intruder)
            ->post("/client-area/notifications/{$notification->id}/read")
            ->assertNotFound();
    }
}
