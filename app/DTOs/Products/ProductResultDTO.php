<?php

namespace App\DTOs\Products;

use App\Models\Product;
use Spatie\LaravelData\Data;

class ProductResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public float $purchase_price,
        public float $sale_price,
        public int $quantity,
        public string $product_type,
        public string $product_unity,
        public string $visibility,
        public string $created_at,
    ) {}

    public static function fromModel(Product $product): static
    {
        return new static(
            id: $product->id,
            name: $product->name,
            purchase_price: $product->purchase_price,
            sale_price: $product->sale_price,
            quantity: $product->quantity,
            product_type: $product->product_type?->value ?? '',
            product_unity: $product->product_unity,
            visibility: $product->visibility->value,
            created_at: $product->created_at?->toISOString() ?? '',
        );
    }
}
