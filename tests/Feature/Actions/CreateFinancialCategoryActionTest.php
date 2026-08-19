<?php

namespace Tests\Feature\Actions;

use App\Actions\FinancialCategories\CreateFinancialCategoryAction;
use App\DTOs\FinancialCategories\CreateFinancialCategoryDTO;
use App\Models\FinancialCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFinancialCategoryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_financial_category_with_valid_data(): void
    {
        $action = app(CreateFinancialCategoryAction::class);
        $dto = CreateFinancialCategoryDTO::from([
            'name' => 'Categoria Financeira',
            'color' => '#FF0000',
            'operation_type' => 'receivable',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('financial_categories', [
            'name' => 'Categoria Financeira',
            'operation_type' => 'receivable',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateFinancialCategoryAction::class);
        $dto = CreateFinancialCategoryDTO::from([
            'name' => 'Categoria Teste',
            'color' => '#0000FF',
            'operation_type' => 'payable',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Categoria financeira criada com sucesso.', $result->message);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateFinancialCategoryAction::class);
        $action->execute('not-a-dto');
    }
}
