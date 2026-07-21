<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message in a ticket thread — written either by the client
 * (user_id) or by a staff member (admin_id).
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property int|null $admin_id
 * @property string $message
 */
#[Fillable(['user_id', 'admin_id', 'message'])]
class TicketReply extends Model
{
    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
