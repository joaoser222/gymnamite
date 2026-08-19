<?php

namespace Tests\Feature\Actions;

use App\Actions\Payables\UpdatePayableAction;
use App\DTOs\Payables\UpdatePayableDTO;
use App\Models\Payable;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePayableActionTest extends TestCase
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

    private function createPayable(array $overrides = []): Payable
    {
        return Payable::query()->create(array_merge([
            'holder_type' => 'supplier',
            'holder_id' => $this->supplier->id,
            'due_date' => '2026-12-31',
            'gross_value' => 100.0,
            'payment_method' => 'cash',
            'operation_type' => 'payable',
        ], $overrides));
    }

    public function test_updates_payable_with_valid_data(): void
    {
        $payable = $this->createPayable();

        $action = app(UpdatePayableAction::class);
        $dto = UpdatePayableDTO::from([
            'id' => $payable->id,
            'supplier_id' => $this->supplier->id,
            'annotations' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('invoices', [
            'id' => $payable->id,
            'annotations' => 'Atualizado',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $payable = $this->createPayable();

        $action = app(UpdatePayableAction::class);
        $dto = UpdatePayableDTO::from([
            'id' => $payable->id,
            'supplier_id' => $this->supplier->id,
            'annotations' => 'Msg',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Conta a pagar atualizada com sucesso.', $result->message);
    }

    public function test_throws_when_payable_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdatePayableAction::class);
        $dto = UpdatePayableDTO::from([
            'id' => 999999,
            'supplier_id' => $this->supplier->id,
            'annotations' => 'X',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdatePayableAction::class);
        $action->execute('not-a-dto');
    }
}
