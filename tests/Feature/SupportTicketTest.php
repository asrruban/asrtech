<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/client-area/tickets')->assertRedirect('/login');
    }

    public function test_client_can_open_a_ticket(): void
    {
        $user = User::factory()->create();
        $department = $this->department();

        $this->actingAs($user)
            ->post('/client-area/tickets', [
                'ticket_department_id' => $department->id,
                'subject' => 'License not activating',
                'priority' => 'high',
                'message' => 'My license key is rejected on install.',
            ])->assertRedirectContains('/client-area/ticket/');

        $ticket = Ticket::query()->sole();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $ticket->ticket_number);
        $this->assertNotNull($ticket->last_reply_at);

        // The opening message is stored as the first reply.
        $reply = $ticket->replies()->sole();
        $this->assertSame($user->id, $reply->user_id);
        $this->assertNull($reply->admin_id);
        $this->assertSame('My license key is rejected on install.', $reply->message);
    }

    public function test_hidden_departments_are_not_offered_or_accepted(): void
    {
        $user = User::factory()->create();
        $visible = $this->department();
        $hidden = $this->department(['name' => 'Internal', 'hidden' => true]);

        $this->actingAs($user)
            ->get('/client-area/tickets/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Support/Create')
                ->has('departments', 1)
                ->where('departments.0.id', $visible->id));

        $this->actingAs($user)
            ->post('/client-area/tickets', [
                'ticket_department_id' => $hidden->id,
                'subject' => 'Sneaky',
                'priority' => 'low',
                'message' => 'Should not work.',
            ])->assertSessionHasErrors('ticket_department_id');
    }

    public function test_tickets_are_private_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = $this->ticket($owner);

        $this->actingAs($owner)
            ->get("/client-area/ticket/{$ticket->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Support/Show')
                ->where('ticket.ticket_number', $ticket->ticket_number)
                ->has('replies', 1));

        $this->actingAs($other)
            ->get("/client-area/ticket/{$ticket->id}")
            ->assertNotFound();
    }

    public function test_client_reply_reopens_the_ticket_for_staff(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket($user, ['status' => TicketStatus::Answered]);

        $this->actingAs($user)
            ->post("/client-area/ticket/{$ticket->id}/reply", [
                'message' => 'Still not working for me.',
            ])->assertRedirect("/client-area/ticket/{$ticket->id}");

        $ticket->refresh();
        $this->assertSame(TicketStatus::CustomerReply, $ticket->status);
        $this->assertSame(2, $ticket->replies()->count());
    }

    public function test_client_can_close_a_ticket_unless_the_department_prevents_it(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket($user);

        $this->actingAs($user)
            ->post("/client-area/ticket/{$ticket->id}/close")
            ->assertRedirect("/client-area/ticket/{$ticket->id}");
        $this->assertSame(TicketStatus::Closed, $ticket->refresh()->status);

        $locked = $this->ticket($user, [], ['prevent_client_closure' => true]);

        $this->actingAs($user)
            ->post("/client-area/ticket/{$locked->id}/close")
            ->assertRedirect("/client-area/ticket/{$locked->id}");
        $this->assertSame(TicketStatus::Open, $locked->refresh()->status);
    }

    /** @param  array<string, mixed>  $attributes */
    private function department(array $attributes = []): TicketDepartment
    {
        return TicketDepartment::query()->create([
            'name' => 'Support',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $departmentAttributes
     */
    private function ticket(User $user, array $attributes = [], array $departmentAttributes = []): Ticket
    {
        $ticket = $user->tickets()->create([
            'ticket_number' => Ticket::newTicketNumber(),
            'ticket_department_id' => $this->department($departmentAttributes)->id,
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
