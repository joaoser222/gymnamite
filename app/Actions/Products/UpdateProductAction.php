<?php

namespace App\Actions\Products;

use App\Actions\BaseAction;
use App\DTOs\Products\ActionResultDTO;
use App\DTOs\Products\UpdateProductDTO;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Repositories\Contracts\ProductRepositoryInterface;

class UpdateProductAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Product::class;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateProductDTO) {
            throw new \InvalidArgumentException('UpdateProductAction requires an UpdateProductDTO.');
        }

        $dto = $input;
        $product = $this->productRepository->findOrFail($dto->id);

        $unity = ProductUnity::query()->where('code', $dto->product_unity)->firstOrFail();

        $this->productRepository->update($product, [
            'name' => $dto->name,
            'product_unity' => $unity->code,
            'product_type' => $dto->product_type,
            'sale_price' => $dto->sale_price,
            'purchase_price' => $dto->purchase_price,
            'quantity' => $dto->quantity,
        ]);

        return ActionResultDTO::success(
            $product->refresh(),
            'Produto atualizado com sucesso.'
        );
    }
}
