<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbound email webhook ("email piping"). Point your provider's inbound
 * route (Mailgun, Postmark, SES, …) at this endpoint with a shared secret.
 * A client reply to a ticket notification email becomes a ticket reply.
 *
 * Expected JSON/form fields: token, from, subject, text (optional: html).
 * The ticket number is matched in the subject as #123456 or [Ticket #123456].
 */
class InboundEmailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $configured = (string) config('asrtech.support.inbound_token', '');

        abort_if($configured === '', 404);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'from' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:500'],
            'text' => ['nullable', 'string', 'max:65535'],
            'html' => ['nullable', 'string', 'max:131072'],
        ]);

        if (! hash_equals($configured, $data['token'])) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if (! preg_match('/#(\d{6})\b/', $data['subject'], $matches)) {
            return response()->json(['message' => 'No ticket reference in subject.'], 422);
        }

        $ticket = Ticket::query()
            ->with('user')
            ->where('ticket_number', $matches[1])
            ->first();

        if ($ticket === null) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Only the ticket owner may reply by email.
        if ($ticket->user === null || ! hash_equals(strtolower($ticket->user->email), strtolower($data['from']))) {
            return response()->json(['message' => 'Sender does not own this ticket.'], 403);
        }

        $body = $this->cleanBody($data['text'] ?? '');

        if ($body === '' && isset($data['html'])) {
            $body = $this->cleanBody(html_entity_decode(strip_tags($data['html'])));
        }

        if ($body === '') {
            return response()->json(['message' => 'Empty reply body.'], 422);
        }

        $ticket->replies()->create([
            'user_id' => $ticket->user->id,
            'message' => $body,
        ]);

        $ticket->update([
            'status' => TicketStatus::CustomerReply,
            'last_reply_at' => now(),
        ]);

        return response()->json(['message' => 'Reply recorded.', 'ticket' => $ticket->ticket_number]);
    }

    /** Strip quoted history and signatures from the plain-text body. */
    private function cleanBody(string $text): string
    {
        $lines = preg_split('/\R/', $text) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            // Stop at the first quote marker.
            if (preg_match('/^(>+\s|On .+ wrote:|From:\s|-----Original Message-----|Sent from my )/i', $line)) {
                break;
            }

            $clean[] = $line;
        }

        return trim(implode("\n", $clean));
    }
}
