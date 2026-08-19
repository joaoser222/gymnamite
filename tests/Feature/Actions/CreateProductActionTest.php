<?php

namespace Tests\Feature\Actions;

use App\Actions\Products\CreateProductAction;
use App\DTOs\Products\CreateProductDTO;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductUnity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProductActionTest extends TestCase
{
    use RefreshDatabase;

    private function ensureUnity(): ProductUnity
    {
        return ProductUnity::query()->firstOrCreate(
            ['code' => 'UN'],
            ['name' => 'Unidade'],
        );
    }

    public function test_creates_a_product_with_valid_data(): void
    {
        $this->ensureUnity();
        $action = app(CreateProductAction::class);

        $dto = new CreateProductDTO(
            name: 'Garrafa',
            purchase_price: 8.5,
            sale_price: 15.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'UN',
            quantity: 12,
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('products', ['name' => 'Garrafa', 'product_unity' => 'UN', 'quantity' => 12]);
    }

    public function test_returns_success_message(): void
    {
        $this->ensureUnity();
        $action = app(CreateProductAction::class);

        $dto = new CreateProductDTO(
            name: 'Camiseta',
            purchase_price: 20.0,
            sale_price: 45.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'UN',
        );

        $result = $action->execute($dto);

        $this->assertSame('Produto criado com sucesso.', $result->message);
    }

    public function test_returns_product_model_in_data(): void
    {
        $this->ensureUnity();
        $action = app(CreateProductAction::class);

        $dto = new CreateProductDTO(
            name: 'Suplemento',
            purchase_price: 50.0,
            sale_price: 89.9,
            product_type: ProductType::SERVICE,
            product_unity: 'UN',
            quantity: 5,
        );

        $result = $action->execute($dto);

        $this->assertInstanceOf(Product::class, $result->data);
        $this->assertSame('Suplemento', $result->data->name);
    }

    public function test_throws_when_product_unity_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $action = app(CreateProductAction::class);
        $dto = new CreateProductDTO(
            name: 'Produto',
            purchase_price: 10.0,
            sale_price: 20.0,
            product_type: ProductType::MERCHANDISE,
            product_unity: 'XX',
        );
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateProductAction::class);
        $action->execute('not-a-dto');
    }
}
