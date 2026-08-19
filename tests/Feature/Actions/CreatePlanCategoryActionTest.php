<?php

namespace Tests\Feature\Actions;

use App\Actions\PlanCategories\CreatePlanCategoryAction;
use App\DTOs\PlanCategories\CreatePlanCategoryDTO;
use App\Models\PlanCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePlanCategoryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_plan_category_with_valid_data(): void
    {
        $action = app(CreatePlanCategoryAction::class);
        $dto = CreatePlanCategoryDTO::from([
            'name' => 'Categoria de Plano',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plan_categories', [
            'name' => 'Categoria de Plano',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreatePlanCategoryAction::class);
        $dto = CreatePlanCategoryDTO::from([
            'name' => 'Plano Teste',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Categoria de plano criada com sucesso.', $result->message);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreatePlanCategoryAction::class);
        $action->execute('not-a-dto');
    }
}
