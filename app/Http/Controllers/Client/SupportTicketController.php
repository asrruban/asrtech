<?php

namespace App\Http\Controllers\Client;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client area support tickets: open a ticket in a visible department,
 * follow the conversation, reply, and close (where the department
 * allows it).
 */
class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('Client/Support/Index', [
            'tickets' => $user->tickets()
                ->with('department:id,name')
                ->withCount('replies')
                ->orderByDesc('last_reply_at')
                ->get()
                ->map(fn (Ticket $ticket) => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status->value,
                    'status_label' => $ticket->status->label(),
                    'priority' => $ticket->priority->label(),
                    'department' => $ticket->department?->name,
                    'replies_count' => $ticket->replies_count,
                    'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $departments = TicketDepartment::query()
            ->where('hidden', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'description']);
        $selectedDepartmentId = $request->integer('department');

        return Inertia::render('Client/Support/Create', [
            'departments' => $departments,
            'selectedDepartmentId' => $departments->contains('id', $selectedDepartmentId)
                ? $selectedDepartmentId
                : $departments->first()?->id,
            'priorities' => TicketPriority::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $data = $request->validate([
            'ticket_department_id' => [
                'required', 'integer',
                // Booleans stringify to '' in exists rules — compare to 0.
                Rule::exists('ticket_departments', 'id')->where('hidden', 0),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(TicketPriority::values())],
            'message' => ['required', 'string', 'max:65535'],
        ]);

        $ticket = $user->tickets()->create([
            'ticket_number' => Ticket::newTicketNumber(),
            'ticket_department_id' => $data['ticket_department_id'],
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'status' => TicketStatus::Open,
            'last_reply_at' => now(),
        ]);

        $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => $data['message'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number opened — we will get back to you shortly.', ['number' => $ticket->ticket_number])]);

        return redirect()->route('support.show', $ticket);
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        $this->authorizeTicket($request, $ticket);

        $ticket->load(['department:id,name,prevent_client_closure', 'replies.user:id,name', 'replies.admin:id,name']);

        return Inertia::render('Client/Support/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'priority' => $ticket->priority->label(),
                'department' => $ticket->department?->name,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'can_close' => $ticket->status !== TicketStatus::Closed
                    && ! $ticket->department?->prevent_client_closure,
            ],
            'replies' => $ticket->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'author' => $reply->admin->name ?? $reply->user->name ?? __('Unknown'),
                'is_staff' => $reply->admin_id !== null,
                'created_at' => $reply->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $this->authorizeTicket($request, $ticket);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:65535'],
        ]);

        $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => $data['message'],
        ]);

        // A client reply (re)opens the ticket and flags it for staff.
        $ticket->update([
            'status' => TicketStatus::CustomerReply,
            'last_reply_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply added.')]);

        return redirect()->route('support.show', $ticket);
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);

        if ($ticket->department?->prevent_client_closure) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Tickets in this department can only be closed by our staff.')]);

            return redirect()->route('support.show', $ticket);
        }

        $ticket->update(['status' => TicketStatus::Closed]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket closed.')]);

        return redirect()->route('support.show', $ticket);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** Tickets are private — respond 404 for anyone but the owner. */
    private function authorizeTicket(Request $request, Ticket $ticket): User
    {
        $user = $this->user($request);
        abort_unless($ticket->user_id === $user->id, 404);

        return $user;
    }
}
