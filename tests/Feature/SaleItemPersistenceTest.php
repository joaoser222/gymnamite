<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Visibility;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleItemPersistenceTest extends TestCase
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

    public function test_authenticated_users_can_create_sale_with_items(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'sales.create');

        $client = $this->createClient();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 2,
            'generate_invoices' => true,
            'discount_value' => 8,
            'annotations' => 'Venda de teste',
            'disable_stock' => false,
            'items' => [
                [
                    'product_id' => $firstProduct->id,
                    'quantity' => 2,
                    'price' => 30,
                ],
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 1,
                    'price' => 15,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));

        $sale = Sale::query()->with('items')->firstOrFail();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(75.0, $sale->gross_value);
        $this->assertSame(8.0, $sale->discount_value);
        $this->assertSame(67.0, $sale->total);
        $this->assertSame('2026-07-10', $sale->first_due_date?->format('Y-m-d'));
        $this->assertSame(2, $sale->installments);
        $this->assertCount(2, $sale->items);
        $this->assertDatabaseCount('invoices', 2);
        $this->assertSame(-2, $firstProduct->quantity);
        $this->assertSame(-1, $secondProduct->quantity);

        $firstInvoice = Invoice::query()->orderBy('installment_number')->firstOrFail();

        $this->assertSame($sale->id, $firstInvoice->billable_id);
        $this->assertSame('sale', $firstInvoice->billable_type);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 2,
        ]);
    }

    public function test_authenticated_users_can_create_sale_without_generating_installments(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'sales.create');

        $client = $this->createClient();
        $product = $this->createProduct('Produto Sem Parcelas');

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'generate_invoices' => false,
            'discount_value' => 0,
            'annotations' => 'Venda sem parcelas',
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 42,
            ]],
        ]);

        $response->assertRedirect(route('sales.index'));

        $sale = Sale::query()->firstOrFail();

        $this->assertNull($sale->first_due_date);
        $this->assertSame(1, $sale->installments);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_authenticated_users_can_update_sale_items(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'sales.update');

        $client = $this->createClient();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'gross_value' => 20,
            'discount_value' => 0,
            'total' => 20,
            'annotations' => null,
            'disable_stock' => false,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 1,
            'price' => 20,
        ]);

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'first_due_date' => '2026-08-10',
            'installments' => 3,
            'discount_value' => 10,
            'annotations' => 'Atualizada',
            'disable_stock' => true,
            'items' => [
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 2,
                    'price' => 25,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));

        $sale->refresh();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(50.0, $sale->gross_value);
        $this->assertSame(10.0, $sale->discount_value);
        $this->assertSame(40.0, $sale->total);
        $this->assertSame(PaymentMethod::PIX, $sale->payment_method);
        $this->assertSame('2026-08-10', $sale->first_due_date?->format('Y-m-d'));
        $this->assertSame(3, $sale->installments);
        $this->assertSame(0, $firstProduct->quantity);
        $this->assertSame(0, $secondProduct->quantity);

        $this->assertDatabaseMissing('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $firstProduct->id,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $secondProduct->id,
            'product_name' => 'Produto B',
            'quantity' => 2,
        ]);
    }

    public function test_finalized_sales_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'sales.update');

        $client = $this->createClient();
        $product = $this->createProduct('Produto Finalizado');

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => BillableStatus::COMPLETED->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'gross_value' => 20,
            'discount_value' => 0,
            'total' => 20,
            'annotations' => null,
            'disable_stock' => false,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Produto Finalizado',
            'quantity' => 1,
            'price' => 20,
        ]);

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'first_due_date' => '2026-08-10',
            'installments' => 3,
            'discount_value' => 10,
            'annotations' => 'Nao deveria atualizar',
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 25,
            ]],
        ]);

        $response->assertForbidden();

        $sale->refresh();

        $this->assertSame(BillableStatus::COMPLETED->value, $sale->status);
        $this->assertSame(20.0, $sale->total);
    }

    public function test_sale_with_disable_stock_does_not_recalculate_product_quantity(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'sales.create');

        $client = $this->createClient();
        $product = $this->createProduct('Produto Sem Estoque');

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'discount_value' => 0,
            'annotations' => null,
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'price' => 10,
            ]],
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->assertSame(0, $product->refresh()->quantity);
    }

    private function createClient(): Client
    {
        return Client::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'document' => '12345678901',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
            'gender' => GenderType::MALE->value,
            'status' => ClientStatus::ACTIVE->value,
            'visibility' => Visibility::VISIBLE->value,
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
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }
}
