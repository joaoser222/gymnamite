<?php

namespace App\DTOs\Purchases;

use App\Models\Purchase;
use Spatie\LaravelData\Data;

class PurchaseResultDTO extends Data
{
    public function __construct(
        public int $id,
        public float $total,
        public string $status,
        public string $payment_method,
        public string $created_at,
        public int $supplier_id,
        public string $supplier_name,
        public array $items,
    ) {}

    public static function fromModel(Purchase $purchase): static
    {
        $items = [];
        foreach ($purchase->items as $item) {
            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->quantity * $item->price,
            ];
        }

        return new static(
            id: $purchase->id,
            total: $purchase->total,
            status: $purchase->status->value,
            payment_method: $purchase->payment_method->value,
            created_at: $purchase->created_at?->toISOString() ?? '',
            supplier_id: $purchase->supplier_id,
            supplier_name: $purchase->supplier?->name ?? '',
            items: $items,
        );
    }
}
