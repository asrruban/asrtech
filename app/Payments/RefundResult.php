<?php

namespace App\Payments;

final readonly class RefundResult
{
    public function __construct(
        public bool $accepted,
        public string $status,
        public ?string $reference = null,
        public ?string $message = null,
    ) {}

    public static function succeeded(string $reference): self
    {
        return new self(true, 'succeeded', $reference);
    }

    public static function processing(string $reference): self
    {
        return new self(true, 'processing', $reference);
    }

    public static function failed(string $message): self
    {
        return new self(false, 'failed', null, $message);
    }
}
