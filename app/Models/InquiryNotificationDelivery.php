<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_inquiry_id
 * @property string $kind
 * @property string $recipient
 * @property string $sender
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $queued_at
 * @property Carbon|null $sent_at
 * @property string|null $last_error
 * @property-read ProjectInquiry $inquiry
 */
#[Fillable(['project_inquiry_id', 'kind', 'recipient', 'sender', 'status', 'attempts', 'queued_at', 'sent_at', 'last_error'])]
class InquiryNotificationDelivery extends Model
{
    protected function casts(): array
    {
        return ['attempts' => 'integer', 'queued_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    /** @return BelongsTo<ProjectInquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(ProjectInquiry::class, 'project_inquiry_id');
    }
}
