<?php

namespace App\Payments;

final readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $method,
        public ?string $reference = null,
        public ?string $message = null,
        public ?string $redirectUrl = null,
    ) {}

    public static function success(string $method, string $reference): self
    {
        return new self(true, $method, $reference);
    }

    public static function failure(string $method, string $message): self
    {
        return new self(false, $method, null, $message);
    }

    /**
     * The customer must complete payment on the gateway's hosted page;
     * the order stays pending until the gateway calls back.
     */
    public static function redirect(string $method, string $url, ?string $reference = null): self
    {
        return new self(false, $method, $reference, null, $url);
    }

    public function needsRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
