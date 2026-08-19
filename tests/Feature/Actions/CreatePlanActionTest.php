<?php

namespace Tests\Feature\Actions;

use App\Actions\Plans\CreatePlanAction;
use App\DTOs\Plans\CreatePlanDTO;
use App\DTOs\Plans\PlanTierDTO;
use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePlanActionTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(): PlanCategory
    {
        return PlanCategory::query()->create(['name' => 'Premium', 'visibility' => 'visible']);
    }

    public function test_creates_a_plan_with_tiers(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Gold',
            'plan_category_id' => $category->id,
            'description' => 'Plano completo',
            'modality_quantity' => 2,
            'tiers' => [
                ['quantity' => 1, 'price' => 99.9],
                ['quantity' => 12, 'price' => 999.9],
            ],
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', ['name' => 'Plano Gold']);
        $this->assertDatabaseHas('plan_tiers', ['plan_id' => $result->data->id, 'quantity' => 1, 'price' => 99.9]);
        $this->assertDatabaseHas('plan_tiers', ['plan_id' => $result->data->id, 'quantity' => 12, 'price' => 999.9]);
    }

    public function test_creates_a_plan_with_modalities(): void
    {
        $category = $this->createCategory();
        $modality = Modality::query()->create(['name' => 'Pilates', 'visibility' => 'visible']);
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Pilates',
            'plan_category_id' => $category->id,
            'modality_quantity' => 1,
            'tiers' => [
                ['quantity' => 1, 'price' => 150.0],
            ],
            'plan_modalities' => [$modality->id],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plan_modalities', [
            'plan_id' => $result->data->id,
            'modality_id' => $modality->id,
        ]);
    }

    public function test_returns_plan_with_loaded_relations(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano VIP',
            'plan_category_id' => $category->id,
            'modality_quantity' => 1,
            'tiers' => [
                ['quantity' => 6, 'price' => 500.0],
            ],
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Plano VIP', $result->data->name);
        $this->assertCount(1, $result->data->tiers);
        $this->assertCount(0, $result->data->modalities);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreatePlanAction::class);
        $action->execute('not-a-dto');
    }
}
