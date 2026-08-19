<?php

namespace Tests\Feature\Actions;

use App\Actions\Supplier\UpdateSupplierAction;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSupplierActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_supplier_with_valid_data(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Nome Antigo',
            'document' => '12345678000195',
            'phone' => '11988887777',
        ]);

        $action = app(UpdateSupplierAction::class);
        $dto = UpdateSupplierDTO::from([
            'id' => $supplier->id,
            'name' => 'Nome Novo',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nome Novo',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Atualizar',
            'document' => '12345678000195',
            'phone' => '11988887777',
        ]);

        $action = app(UpdateSupplierAction::class);
        $dto = UpdateSupplierDTO::from([
            'id' => $supplier->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Fornecedor atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_supplier_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateSupplierAction::class);
        $dto = UpdateSupplierDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateSupplierAction::class);
        $action->execute('not-a-dto');
    }
}
