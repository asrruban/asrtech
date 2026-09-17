<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $target_date
 */
#[Fillable(['user_id', 'project_inquiry_id', 'quote_id', 'title', 'description', 'status', 'target_date'])]
class Project extends Model
{
    public const STATUSES = ['planned', 'active', 'paused', 'completed', 'cancelled'];

    protected $attributes = ['status' => 'planned'];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ProjectInquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(ProjectInquiry::class, 'project_inquiry_id');
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return HasMany<ProjectMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    /** @return HasMany<ProjectUpdate, $this> */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    /** @return HasMany<ProjectFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /** @return HasMany<ProjectApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(ProjectApproval::class);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }
}
