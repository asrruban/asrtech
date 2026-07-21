<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin support inbox: tickets opened by clients, filterable by status,
 * with staff replies (marking the ticket Answered) and status control.
 */
class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'awaiting');

        $query = Ticket::query()
            ->with(['user:id,name,email', 'department:id,name'])
            ->orderByDesc('last_reply_at');

        if ($status === 'awaiting') {
            $query->awaitingReply();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $byStatus = Ticket::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Support/Tickets/Index', [
            'tickets' => $query->get()->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'priority' => $ticket->priority->value,
                'priority_label' => $ticket->priority->label(),
                'client' => $ticket->user?->only(['id', 'name', 'email']),
                'department' => $ticket->department?->name,
                'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
            ]),
            'counts' => [
                'awaiting' => (int) collect(TicketStatus::awaitingReply())
                    ->sum(fn (TicketStatus $s) => (int) ($byStatus[$s->value] ?? 0)),
                'all' => (int) $byStatus->sum(),
                ...collect(TicketStatus::values())
                    ->mapWithKeys(fn (string $value) => [$value => (int) ($byStatus[$value] ?? 0)])
                    ->all(),
            ],
            'statuses' => TicketStatus::options(),
            'activeStatus' => $status,
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load([
            'user:id,name,email',
            'department:id,name',
            'replies.user:id,name',
            'replies.admin:id,name',
        ]);

        return Inertia::render('Admin/Support/Tickets/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'priority_label' => $ticket->priority->label(),
                'client' => $ticket->user?->only(['id', 'name', 'email']),
                'department' => $ticket->department?->name,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
            ],
            'replies' => $ticket->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'author' => $reply->admin->name ?? $reply->user->name ?? __('Unknown'),
                'is_staff' => $reply->admin_id !== null,
                'created_at' => $reply->created_at?->toIso8601String(),
            ]),
            'statuses' => TicketStatus::options(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:65535'],
        ]);

        $ticket->replies()->create([
            'admin_id' => $request->user('admin')?->id,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'status' => TicketStatus::Answered,
            'last_reply_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply sent — ticket marked as answered.')]);

        return redirect()->route('admin.support.tickets.show', $ticket);
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(TicketStatus::values())],
        ]);

        $ticket->update(['status' => $data['status']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket status updated.')]);

        return redirect()->route('admin.support.tickets.show', $ticket);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket deleted.')]);

        return redirect()->route('admin.support.tickets.index');
    }
}
