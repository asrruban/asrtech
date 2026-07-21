<?php

namespace App\Services;

use App\Models\PromotionCode;
use App\Models\TaxRate;

final readonly class PricingQuote
{
    public function __construct(
        public string $currency,
        public float $subtotal,
        public float $setupFee,
        public float $discountAmount,
        public float $taxAmount,
        public float $total,
        public ?PromotionCode $promotion,
        public ?TaxRate $taxRate,
        public bool $taxPending,
    ) {}
}
