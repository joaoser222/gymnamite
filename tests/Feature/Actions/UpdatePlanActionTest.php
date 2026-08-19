<?php

namespace Tests\Feature\Actions;

use App\Actions\Plans\UpdatePlanAction;
use App\DTOs\Plans\PlanTierDTO;
use App\DTOs\Plans\UpdatePlanDTO;
use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use App\Models\PlanTier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePlanActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_plan_and_replaces_tiers(): void
    {
        $category = PlanCategory::query()->create(['name' => 'Basico', 'visibility' => 'visible']);
        $plan = Plan::query()->create([
            'name' => 'Plano Antigo',
            'plan_category_id' => $category->id,
            'modality_quantity' => 1,
        ]);
        PlanTier::query()->create(['plan_id' => $plan->id, 'quantity' => 1, 'price' => 100.0]);

        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: $plan->id,
            name: 'Plano Novo',
            plan_category_id: $category->id,
            modality_quantity: 2,
            tiers: [new PlanTierDTO(quantity: 3, price: 250.0)],
            plan_modalities: [],
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Plano Novo', 'modality_quantity' => 2]);
        $this->assertDatabaseMissing('plan_tiers', ['plan_id' => $plan->id, 'quantity' => 1]);
        $this->assertDatabaseHas('plan_tiers', ['plan_id' => $plan->id, 'quantity' => 3, 'price' => 250.0]);
    }

    public function test_updates_plan_modalities(): void
    {
        $category = PlanCategory::query()->create(['name' => 'Basico', 'visibility' => 'visible']);
        $modality1 = Modality::query()->create(['name' => 'Pilates', 'visibility' => 'visible']);
        $modality2 = Modality::query()->create(['name' => 'Yoga', 'visibility' => 'visible']);

        $plan = Plan::query()->create([
            'name' => 'Plano',
            'plan_category_id' => $category->id,
            'modality_quantity' => 2,
        ]);
        PlanTier::query()->create(['plan_id' => $plan->id, 'quantity' => 1, 'price' => 100.0]);
        PlanModality::query()->create(['plan_id' => $plan->id, 'modality_id' => $modality1->id]);

        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: $plan->id,
            name: 'Plano',
            plan_category_id: $category->id,
            modality_quantity: 2,
            tiers: [new PlanTierDTO(quantity: 1, price: 100.0)],
            plan_modalities: [$modality2->id],
        );

        $result = $action->execute($dto);

        $this->assertDatabaseMissing('plan_modalities', ['plan_id' => $plan->id, 'modality_id' => $modality1->id]);
        $this->assertDatabaseHas('plan_modalities', ['plan_id' => $plan->id, 'modality_id' => $modality2->id]);
    }

    public function test_throws_when_plan_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $category = PlanCategory::query()->create(['name' => 'X', 'visibility' => 'visible']);
        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: 999999,
            name: 'Inexistente',
            plan_category_id: $category->id,
            modality_quantity: 1,
            tiers: [new PlanTierDTO(quantity: 1, price: 10.0)],
            plan_modalities: [],
        );
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdatePlanAction::class);
        $action->execute('not-a-dto');
    }
}
