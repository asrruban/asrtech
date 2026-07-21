<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Admin;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_defaults_to_tickets_awaiting_a_reply(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();

        $this->ticket($user);
        $this->ticket($user, ['status' => TicketStatus::CustomerReply]);
        $this->ticket($user, ['status' => TicketStatus::Answered]);
        $this->ticket($user, ['status' => TicketStatus::Closed]);

        $this->get('/admin/support/tickets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Support/Tickets/Index')
                ->has('tickets', 2)
                ->where('activeStatus', 'awaiting')
                ->where('counts.awaiting', 2)
                ->where('counts.all', 4)
                ->where('counts.answered', 1)
                ->where('counts.closed', 1));

        $this->get('/admin/support/tickets?status=closed')
            ->assertInertia(fn (Assert $page) => $page->has('tickets', 1));

        $this->get('/admin/support/tickets?status=all')
            ->assertInertia(fn (Assert $page) => $page->has('tickets', 4));
    }

    public function test_admin_reply_marks_the_ticket_answered(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $ticket = $this->ticket(User::factory()->create());

        $this->post("/admin/support/tickets/{$ticket->id}/reply", [
            'message' => 'We have reissued your license.',
        ])->assertRedirect("/admin/support/tickets/{$ticket->id}");

        $ticket->refresh();
        $this->assertSame(TicketStatus::Answered, $ticket->status);

        $reply = $ticket->replies()->get()->last();
        $this->assertSame($admin->id, $reply->admin_id);
        $this->assertNull($reply->user_id);
    }

    public function test_admin_can_change_status_and_delete_tickets(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $ticket = $this->ticket(User::factory()->create());

        $this->patch("/admin/support/tickets/{$ticket->id}/status", [
            'status' => 'on_hold',
        ])->assertRedirect("/admin/support/tickets/{$ticket->id}");
        $this->assertSame(TicketStatus::OnHold, $ticket->refresh()->status);

        $this->delete("/admin/support/tickets/{$ticket->id}")
            ->assertRedirect('/admin/support/tickets');
        $this->assertFalse(Ticket::query()->whereKey($ticket->id)->exists());
    }

    public function test_show_page_includes_the_conversation(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $ticket = $this->ticket(User::factory()->create(['name' => 'Rifat']));

        $this->get("/admin/support/tickets/{$ticket->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Support/Tickets/Show')
                ->where('ticket.ticket_number', $ticket->ticket_number)
                ->where('ticket.client.name', 'Rifat')
                ->has('replies', 1)
                ->where('replies.0.is_staff', false));
    }

    public function test_sidebar_badge_counts_unanswered_tickets_for_admins(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();

        $this->ticket($user);
        $this->ticket($user, ['status' => TicketStatus::CustomerReply]);
        $this->ticket($user, ['status' => TicketStatus::Answered]);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminBadges.unansweredTickets', 2));
    }

    public function test_departments_with_tickets_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $ticket = $this->ticket(User::factory()->create());

        $this->delete("/admin/support/departments/{$ticket->ticket_department_id}")
            ->assertRedirect('/admin/support/departments');

        $this->assertTrue(
            TicketDepartment::query()->whereKey($ticket->ticket_department_id)->exists(),
        );
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Ticket Admin',
            'email' => 'ticket-admin@example.com',
            'password' => 'a-secure-password',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function ticket(User $user, array $attributes = []): Ticket
    {
        $department = TicketDepartment::query()->firstOrCreate(['name' => 'Support']);

        $ticket = $user->tickets()->create([
            'ticket_number' => Ticket::newTicketNumber(),
            'ticket_department_id' => $department->id,
            'subject' => 'Help needed',
            'status' => TicketStatus::Open,
            'priority' => 'medium',
            'last_reply_at' => now(),
            ...$attributes,
        ]);

        $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => 'Opening message.',
        ]);

        return $ticket;
    }
}
