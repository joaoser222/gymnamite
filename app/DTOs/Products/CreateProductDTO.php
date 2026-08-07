<?php

namespace App\DTOs\Products;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\ProductType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateProductDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Numeric, Min(0)]
        public float $purchase_price,

        #[Required, Numeric, Min(0)]
        public float $sale_price,

        #[Required, Enum(ProductType::class)]
        public ProductType $product_type,

        #[Required, StringType, Max(10)]
        public string $product_unity,

        #[Nullable, IntegerType, Min(0)]
        public ?int $quantity = 0,
    ) {}
}
