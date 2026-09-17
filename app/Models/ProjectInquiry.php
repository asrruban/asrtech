<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $follow_up_at
 */
#[Fillable(['name', 'email', 'service', 'message', 'status', 'user_id', 'assigned_admin_id', 'quote_id', 'internal_notes', 'follow_up_at'])]
class ProjectInquiry extends Model
{
    public const STATUSES = ['new', 'reviewed', 'awaiting_client', 'quoted', 'won', 'closed'];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['follow_up_at' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return HasOne<Project, $this> */
    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}
