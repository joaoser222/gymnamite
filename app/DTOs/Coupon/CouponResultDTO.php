<?php

namespace App\DTOs\Coupon;

use App\Models\Coupon;
use Spatie\LaravelData\Data;

class CouponResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $code,
        public float $percent,
        public ?float $discount_limit,
        public ?string $duration,
        public ?string $expiration_date,
        public string $created_at,
    ) {}

    public static function fromModel(Coupon $coupon): static
    {
        return new static(
            id: $coupon->id,
            code: $coupon->code,
            percent: $coupon->percent,
            discount_limit: $coupon->discount_limit,
            duration: $coupon->duration,
            expiration_date: $coupon->expiration_date?->format('Y-m-d'),
            created_at: $coupon->created_at?->toISOString() ?? '',
        );
    }
}
