<?php

namespace Tests\Feature\Actions;

use App\Actions\Products\UpdateProductAction;
use App\DTOs\Products\UpdateProductDTO;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductUnity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProductActionTest extends TestCase
{
    use RefreshDatabase;

    private function ensureUnity(): ProductUnity
    {
        return ProductUnity::query()->firstOrCreate(
            ['code' => 'UN'],
            ['name' => 'Unidade'],
        );
    }

    public function test_updates_a_product_with_valid_data(): void
    {
        $this->ensureUnity();
        $product = Product::query()->create([
            'name' => 'Garrafa',
            'product_unity' => 'UN',
            'product_type' => ProductType::MERCHANDISE,
            'purchase_price' => 8.5,
            'sale_price' => 15.0,
            'quantity' => 10,
        ]);

        $action = app(UpdateProductAction::class);
        $dto = new UpdateProductDTO(
            id: $product->id,
            name: 'Garrafa Atualizada',
            purchase_price: 9.0,
            sale_price: 18.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'UN',
            quantity: 20,
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Garrafa Atualizada', 'quantity' => 20]);
    }

    public function test_returns_success_message(): void
    {
        $this->ensureUnity();
        $product = Product::query()->create([
            'name' => 'Item',
            'product_unity' => 'UN',
            'product_type' => ProductType::MERCHANDISE,
            'purchase_price' => 5.0,
            'sale_price' => 10.0,
        ]);

        $action = app(UpdateProductAction::class);
        $dto = new UpdateProductDTO(
            id: $product->id,
            name: 'Item Novo',
            purchase_price: 6.0,
            sale_price: 12.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'UN',
        );

        $result = $action->execute($dto);

        $this->assertSame('Produto atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_product_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->ensureUnity();
        $action = app(UpdateProductAction::class);
        $dto = new UpdateProductDTO(
            id: 999999,
            name: 'Inexistente',
            purchase_price: 1.0,
            sale_price: 2.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'UN',
        );
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateProductAction::class);
        $action->execute('not-a-dto');
    }
}
