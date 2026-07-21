<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * WHMCS-style support ticket: the opening message is the first reply,
 * so the ticket row is the subject + status + priority envelope around
 * the conversation thread.
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property int $ticket_department_id
 * @property string $subject
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon|null $last_reply_at
 */
#[Fillable([
    'ticket_number', 'user_id', 'ticket_department_id', 'subject',
    'status', 'priority', 'last_reply_at',
])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'last_reply_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TicketDepartment, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class, 'ticket_department_id');
    }

    /** @return HasMany<TicketReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at')->orderBy('id');
    }

    /** @param  Builder<self>  $query */
    public function scopeAwaitingReply(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::awaitingReply());
    }

    /** WHMCS-style six digit ticket number, unique across all tickets. */
    public static function newTicketNumber(): string
    {
        do {
            $number = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::query()->where('ticket_number', $number)->exists());

        return $number;
    }
}
