<?php

namespace Tests\Feature\Actions;

use App\Actions\Payables\CreatePayableAction;
use App\DTOs\Payables\CreatePayableDTO;
use App\Models\Payable;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePayableActionTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::query()->create([
            'name' => 'Fornecedor Teste',
            'document' => '12345678000195',
            'phone' => '11999999999',
        ]);
    }

    public function test_creates_payable_with_valid_data(): void
    {
        $action = app(CreatePayableAction::class);
        $dto = CreatePayableDTO::from([
            'supplier_id' => $this->supplier->id,
            'due_date' => '2026-12-31',
            'total' => 150.0,
            'payment_method' => 'cash',
            'operation_type' => 'payable',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('invoices', [
            'operation_type' => 'payable',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreatePayableAction::class);
        $dto = CreatePayableDTO::from([
            'supplier_id' => $this->supplier->id,
            'due_date' => '2026-12-31',
            'total' => 100.0,
            'payment_method' => 'cash',
            'operation_type' => 'payable',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Conta a pagar criada com sucesso.', $result->message);
    }

    public function test_stores_optional_annotations(): void
    {
        $action = app(CreatePayableAction::class);
        $dto = CreatePayableDTO::from([
            'supplier_id' => $this->supplier->id,
            'due_date' => '2026-12-31',
            'total' => 200.0,
            'payment_method' => 'pix',
            'operation_type' => 'payable',
            'annotations' => 'Observação teste',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('invoices', [
            'annotations' => 'Observação teste',
        ]);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreatePayableAction::class);
        $action->execute('not-a-dto');
    }
}
