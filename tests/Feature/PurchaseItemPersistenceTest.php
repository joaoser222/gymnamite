<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseItemPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    public function test_authenticated_users_can_create_purchase_with_items(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.create');

        $supplier = $this->createSupplier();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'discount_value' => 10,
            'annotations' => 'Compra de teste',
            'disable_stock' => false,
            'items' => [
                [
                    'product_id' => $firstProduct->id,
                    'quantity' => 2,
                    'price' => 15.5,
                ],
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 1,
                    'price' => 20,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase = Purchase::query()->with('items')->firstOrFail();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(51.0, $purchase->gross_value);
        $this->assertSame(10.0, $purchase->discount_value);
        $this->assertSame(41.0, $purchase->total);
        $this->assertCount(2, $purchase->items);
        $this->assertSame(2, $firstProduct->quantity);
        $this->assertSame(1, $secondProduct->quantity);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 2,
        ]);
    }

    public function test_authenticated_users_can_update_purchase_items(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.update');

        $supplier = $this->createSupplier();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'gross_value' => 10,
            'discount_value' => 0,
            'total' => 10,
            'annotations' => null,
            'disable_stock' => false,
            'visibility' => 'visible',
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 1,
            'price' => 10,
        ]);

        $response = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'discount_value' => 5,
            'annotations' => 'Atualizada',
            'disable_stock' => true,
            'items' => [
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 3,
                    'price' => 12,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase->refresh();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(36.0, $purchase->gross_value);
        $this->assertSame(5.0, $purchase->discount_value);
        $this->assertSame(31.0, $purchase->total);
        $this->assertSame(PaymentMethod::PIX, $purchase->payment_method);
        $this->assertSame(0, $firstProduct->quantity);
        $this->assertSame(0, $secondProduct->quantity);

        $this->assertDatabaseMissing('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $secondProduct->id,
            'product_name' => 'Produto B',
            'quantity' => 3,
        ]);
    }

    public function test_purchase_with_disable_stock_does_not_recalculate_product_quantity(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.create');

        $supplier = $this->createSupplier();
        $product = $this->createProduct('Produto Sem Estoque');

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'discount_value' => 0,
            'annotations' => null,
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'price' => 10,
            ]],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $this->assertSame(0, $product->refresh()->quantity);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'name' => 'Fornecedor Teste',
            'email' => 'fornecedor@example.com',
            'document' => '12345678901234',
            'phone' => '11999999999',
            'visibility' => 'visible',
        ]);
    }

    private function createProduct(string $name): Product
    {
        ProductUnity::query()->firstOrCreate([
            'code' => 'UN',
        ], [
            'name' => 'Unidade',
        ]);

        return Product::query()->create([
            'name' => $name,
            'purchase_price' => 10,
            'sale_price' => 15,
            'quantity' => 0,
            'product_type' => ProductType::MERCHANDISE->value,
            'product_unity' => 'UN',
            'visibility' => 'visible',
        ]);
    }
}
