<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BillableItemService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncPurchaseItems(Purchase $purchase, array $items, float $discountValue = 0): void
    {
        $this->syncItems(
            document: $purchase,
            items: $items,
            itemModelClass: PurchaseItem::class,
            foreignKey: 'purchase_id',
            discountValue: $discountValue,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncSaleItems(Sale $sale, array $items, float $discountValue = 0): void
    {
        $this->syncItems(
            document: $sale,
            items: $items,
            itemModelClass: SaleItem::class,
            foreignKey: 'sale_id',
            discountValue: $discountValue,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  class-string<Model>  $itemModelClass
     */
    private function syncItems(
        Model $document,
        array $items,
        string $itemModelClass,
        string $foreignKey,
        float $discountValue,
    ): void {
        $normalizedItems = $this->normalizeItems($items);
        $products = Product::query()
            ->whereKey($normalizedItems->pluck('product_id')->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        $document->items()->delete();

        $grossValue = 0.0;

        foreach ($normalizedItems as $item) {
            $price = $item['price'];
            $quantity = $item['quantity'];

            $grossValue += $price * $quantity;

            $itemModelClass::query()->create([
                'product_id' => $item['product_id'],
                'product_name' => (string) $products[$item['product_id']]->name,
                'price' => $price,
                'quantity' => $quantity,
                $foreignKey => $document->getKey(),
            ]);
        }

        $document->update([
            'gross_value' => $grossValue,
            'discount_value' => $discountValue,
            'total' => $grossValue - $discountValue,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, array{product_id:int, quantity:int, price:float}>
     */
    private function normalizeItems(array $items): Collection
    {
        return collect($items)
            ->map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'price' => round((float) $item['price'], 4),
            ]);
    }
}
