<?php

namespace Tests\Feature\Actions;

use App\Actions\PlanCategories\UpdatePlanCategoryAction;
use App\DTOs\PlanCategories\UpdatePlanCategoryDTO;
use App\Models\PlanCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePlanCategoryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_plan_category_with_valid_data(): void
    {
        $category = PlanCategory::query()->create([
            'name' => 'Categoria Antiga',
        ]);

        $action = app(UpdatePlanCategoryAction::class);
        $dto = UpdatePlanCategoryDTO::from([
            'id' => $category->id,
            'name' => 'Categoria Nova',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plan_categories', [
            'id' => $category->id,
            'name' => 'Categoria Nova',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $category = PlanCategory::query()->create([
            'name' => 'Atualizar',
        ]);

        $action = app(UpdatePlanCategoryAction::class);
        $dto = UpdatePlanCategoryDTO::from([
            'id' => $category->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Categoria de plano atualizada com sucesso.', $result->message);
    }

    public function test_throws_when_category_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdatePlanCategoryAction::class);
        $dto = UpdatePlanCategoryDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdatePlanCategoryAction::class);
        $action->execute('not-a-dto');
    }
}
