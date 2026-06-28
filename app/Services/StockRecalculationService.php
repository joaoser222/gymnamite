<?php

namespace App\Services;

use App\Enums\Visibility;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Collection;

class StockRecalculationService
{
    public function recalculateProduct(Product|int $product): Product
    {
        $model = $product instanceof Product
            ? $product
            : Product::query()->findOrFail($product);

        $model->update([
            'quantity' => $this->resolvedStockQuantity((int) $model->getKey()),
        ]);

        return $model->refresh();
    }

    /**
     * @param  iterable<int, Product|int>  $products
     */
    public function recalculateProducts(iterable $products): void
    {
        $productIds = collect($products)
            ->map(fn (Product|int $product): int => $product instanceof Product ? (int) $product->getKey() : $product)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return;
        }

        /** @var Collection<int, Product> $models */
        $models = Product::query()->whereKey($productIds->all())->get();

        foreach ($models as $model) {
            $model->update([
                'quantity' => $this->resolvedStockQuantity((int) $model->getKey()),
            ]);
        }
    }

    public function recalculateAll(): void
    {
        Product::query()->select('id')->chunkById(100, function (Collection $products): void {
            $this->recalculateProducts($products->all());
        });
    }

    private function resolvedStockQuantity(int $productId): int
    {
        $purchasedQuantity = (int) PurchaseItem::query()
            ->where('product_id', $productId)
            ->whereHas('purchase', function ($query): void {
                $query
                    ->where('disable_stock', false)
                    ->where('visibility', '!=', Visibility::ARCHIVED->value);
            })
            ->sum('quantity');

        $soldQuantity = (int) SaleItem::query()
            ->where('product_id', $productId)
            ->whereHas('sale', function ($query): void {
                $query
                    ->where('disable_stock', false)
                    ->where('visibility', '!=', Visibility::ARCHIVED->value);
            })
            ->sum('quantity');

        return $purchasedQuantity - $soldQuantity;
    }
}
