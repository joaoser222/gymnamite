<?php

namespace App\DTOs\Sales;

use App\Models\Sale;
use Spatie\LaravelData\Data;

class SaleResultDTO extends Data
{
    public function __construct(
        public int $id,
        public float $total,
        public string $status,
        public string $payment_method,
        public string $created_at,
        public int $client_id,
        public string $client_name,
        public array $items,
    ) {}

    public static function fromModel(Sale $sale): static
    {
        $items = [];
        foreach ($sale->items as $item) {
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
            id: $sale->id,
            total: $sale->total,
            status: $sale->status->value,
            payment_method: $sale->payment_method->value,
            created_at: $sale->created_at?->toISOString() ?? '',
            client_id: $sale->client_id,
            client_name: $sale->client?->name ?? '',
            items: $items,
        );
    }
}
