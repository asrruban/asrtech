<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Custom field attached to a support department, shown on the ticket
 * submission form (WHMCS tblcustomfields with type=support).
 *
 * @property int $id
 * @property int $ticket_department_id
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property string|null $validation
 * @property string|null $select_options
 * @property bool $required
 * @property bool $admin_only
 * @property int $sort_order
 */
#[Fillable([
    'name', 'type', 'description', 'validation', 'select_options',
    'required', 'admin_only', 'sort_order',
])]
class TicketDepartmentField extends Model
{
    /** @var array<string, string> Field type key => label, WHMCS custom field types. */
    public const TYPES = [
        'text' => 'Text Box',
        'link' => 'Link',
        'dropdown' => 'Drop Down',
        'tickbox' => 'Tick Box',
        'textarea' => 'Text Area',
        'password' => 'Password',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'admin_only' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<TicketDepartment, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class, 'ticket_department_id');
    }
}
