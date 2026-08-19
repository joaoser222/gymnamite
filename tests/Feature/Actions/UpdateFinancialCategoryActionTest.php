<?php

namespace Tests\Feature\Actions;

use App\Actions\FinancialCategories\UpdateFinancialCategoryAction;
use App\DTOs\FinancialCategories\UpdateFinancialCategoryDTO;
use App\Models\FinancialCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFinancialCategoryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_financial_category_with_valid_data(): void
    {
        $category = FinancialCategory::query()->create([
            'name' => 'Categoria Antiga',
            'color' => '#FF0000',
            'operation_type' => 'receivable',
        ]);

        $action = app(UpdateFinancialCategoryAction::class);
        $dto = UpdateFinancialCategoryDTO::from([
            'id' => $category->id,
            'name' => 'Categoria Nova',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('financial_categories', [
            'id' => $category->id,
            'name' => 'Categoria Nova',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $category = FinancialCategory::query()->create([
            'name' => 'Atualizar',
            'color' => '#FF0000',
            'operation_type' => 'receivable',
        ]);

        $action = app(UpdateFinancialCategoryAction::class);
        $dto = UpdateFinancialCategoryDTO::from([
            'id' => $category->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Categoria financeira atualizada com sucesso.', $result->message);
    }

    public function test_throws_when_category_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateFinancialCategoryAction::class);
        $dto = UpdateFinancialCategoryDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateFinancialCategoryAction::class);
        $action->execute('not-a-dto');
    }
}
