<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_inbound_reply_is_recorded_on_the_ticket(): void
    {
        config()->set('asrtech.support.inbound_token', 'secret-token');
        $ticket = $this->ticket();

        $this->postJson('/api/inbound-email', [
            'token' => 'secret-token',
            'from' => $ticket->user->email,
            'subject' => "RE: {$ticket->subject} [Ticket #{$ticket->ticket_number}]",
            'text' => "Thanks, that solved it!\n\nOn Tue, Someone wrote:\n> quoted history",
        ])->assertOk()->assertJson(['ticket' => $ticket->ticket_number]);

        $reply = $ticket->replies()->sole();
        $this->assertSame('Thanks, that solved it!', $reply->message);
        $this->assertSame(TicketStatus::CustomerReply, $ticket->fresh()->status);
    }

    public function test_wrong_token_is_rejected(): void
    {
        config()->set('asrtech.support.inbound_token', 'secret-token');
        $ticket = $this->ticket();

        $this->postJson('/api/inbound-email', [
            'token' => 'wrong',
            'from' => $ticket->user->email,
            'subject' => "RE: x [Ticket #{$ticket->ticket_number}]",
            'text' => 'hello',
        ])->assertUnauthorized();

        $this->assertSame(0, $ticket->replies()->count());
    }

    public function test_non_owner_cannot_reply_by_email(): void
    {
        config()->set('asrtech.support.inbound_token', 'secret-token');
        $ticket = $this->ticket();

        $this->postJson('/api/inbound-email', [
            'token' => 'secret-token',
            'from' => 'attacker@example.com',
            'subject' => "RE: x [Ticket #{$ticket->ticket_number}]",
            'text' => 'injected reply',
        ])->assertForbidden();

        $this->assertSame(0, $ticket->replies()->count());
    }

    public function test_subject_without_ticket_reference_is_unprocessable(): void
    {
        config()->set('asrtech.support.inbound_token', 'secret-token');

        $this->postJson('/api/inbound-email', [
            'token' => 'secret-token',
            'from' => 'anyone@example.com',
            'subject' => 'Hello there',
            'text' => 'hi',
        ])->assertStatus(422);
    }

    private function ticket(): Ticket
    {
        $user = User::factory()->create(['email' => 'client@example.com']);
        $department = TicketDepartment::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'enabled' => true,
        ]);

        return Ticket::query()->create([
            'ticket_number' => Ticket::newTicketNumber(),
            'user_id' => $user->id,
            'ticket_department_id' => $department->id,
            'subject' => 'Need help',
            'status' => TicketStatus::Answered,
            'priority' => 'medium',
        ]);
    }
}
