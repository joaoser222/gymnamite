<?php

namespace Tests\Feature\Actions;

use App\Actions\Supplier\CreateSupplierAction;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSupplierActionTest extends TestCase
{
    use RefreshDatabase;

    private function validData(): array
    {
        return [
            'name' => 'Fornecedor Teste',
            'document' => '12345678000195',
            'email' => 'fornecedor@teste.com',
            'phone' => '11999999999',
        ];
    }

    public function test_creates_supplier_with_valid_data(): void
    {
        $action = app(CreateSupplierAction::class);
        $dto = CreateSupplierDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Fornecedor Teste',
            'document' => '12345678000195',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateSupplierAction::class);
        $dto = CreateSupplierDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertSame('Fornecedor criado com sucesso.', $result->message);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateSupplierAction::class);
        $action->execute('not-a-dto');
    }
}
