<?php

namespace Tests\Feature\Actions;

use App\Actions\CostCenters\UpdateCostCenterAction;
use App\DTOs\CostCenters\UpdateCostCenterDTO;
use App\Models\CostCenter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCostCenterActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_cost_center_with_valid_data(): void
    {
        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Antigo',
            'color' => '#FF0000',
            'operation_type' => 'payable',
        ]);

        $action = app(UpdateCostCenterAction::class);
        $dto = UpdateCostCenterDTO::from([
            'id' => $costCenter->id,
            'name' => 'Centro Novo',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('cost_centers', [
            'id' => $costCenter->id,
            'name' => 'Centro Novo',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $costCenter = CostCenter::query()->create([
            'name' => 'Atualizar',
            'color' => '#FF0000',
            'operation_type' => 'payable',
        ]);

        $action = app(UpdateCostCenterAction::class);
        $dto = UpdateCostCenterDTO::from([
            'id' => $costCenter->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Centro de custo atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_cost_center_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateCostCenterAction::class);
        $dto = UpdateCostCenterDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateCostCenterAction::class);
        $action->execute('not-a-dto');
    }
}
