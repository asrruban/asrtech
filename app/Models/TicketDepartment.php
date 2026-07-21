<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS-style support ticket department. Behaviour flags mirror the
 * department options in WHMCS; the mail_* columns hold the POP cron
 * import configuration (secrets encrypted, never sent to the page).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $email
 * @property bool $clients_only
 * @property bool $pipe_replies_only
 * @property bool $no_autoresponder
 * @property bool $feedback_request
 * @property bool $prevent_client_closure
 * @property bool $hidden
 * @property int $sort_order
 * @property string $mail_provider
 * @property string|null $mail_hostname
 * @property int $mail_port
 * @property string|null $mail_email
 * @property string|null $mail_password
 * @property string|null $mail_client_id
 * @property string|null $mail_client_secret
 */
#[Fillable([
    'name', 'description', 'email', 'clients_only', 'pipe_replies_only',
    'no_autoresponder', 'feedback_request', 'prevent_client_closure',
    'hidden', 'sort_order', 'mail_provider', 'mail_hostname', 'mail_port',
    'mail_email', 'mail_password', 'mail_client_id', 'mail_client_secret',
])]
class TicketDepartment extends Model
{
    /** @var array<string, string> Mail provider key => label. */
    public const MAIL_PROVIDERS = [
        'pop3imap' => 'POP3/IMAP',
        'google' => 'Google',
        'microsoft' => 'Microsoft',
    ];

    protected function casts(): array
    {
        return [
            'clients_only' => 'boolean',
            'pipe_replies_only' => 'boolean',
            'no_autoresponder' => 'boolean',
            'feedback_request' => 'boolean',
            'prevent_client_closure' => 'boolean',
            'hidden' => 'boolean',
            'sort_order' => 'integer',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
            'mail_client_secret' => 'encrypted',
        ];
    }

    /** @return BelongsToMany<Admin, $this> */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class);
    }

    /** @return HasMany<TicketDepartmentField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(TicketDepartmentField::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
