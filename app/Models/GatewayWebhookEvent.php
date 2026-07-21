<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $gateway
 * @property string $external_id
 * @property string|null $event_type
 * @property string $status
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $headers
 * @property string $payload_hash
 * @property int $attempts
 * @property int $duplicate_count
 * @property int|null $response_code
 * @property string|null $last_error
 * @property CarbonInterface|null $processing_started_at
 * @property CarbonInterface|null $verified_at
 * @property CarbonInterface|null $processed_at
 * @property CarbonInterface|null $last_received_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'gateway',
    'external_id',
    'event_type',
    'status',
    'payload',
    'headers',
    'payload_hash',
    'attempts',
    'duplicate_count',
    'response_code',
    'last_error',
    'processing_started_at',
    'verified_at',
    'processed_at',
    'last_received_at',
])]
class GatewayWebhookEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_PROCESSED,
            self::STATUS_FAILED,
        ];
    }

    public function canReplay(): bool
    {
        return $this->verified_at !== null && is_array($this->payload);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'attempts' => 'integer',
            'duplicate_count' => 'integer',
            'response_code' => 'integer',
            'processing_started_at' => 'datetime',
            'verified_at' => 'datetime',
            'processed_at' => 'datetime',
            'last_received_at' => 'datetime',
        ];
    }
}
