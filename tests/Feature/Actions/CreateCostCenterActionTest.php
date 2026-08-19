<?php

namespace Tests\Feature\Actions;

use App\Actions\CostCenters\CreateCostCenterAction;
use App\DTOs\CostCenters\CreateCostCenterDTO;
use App\Models\CostCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCostCenterActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_cost_center_with_valid_data(): void
    {
        $action = app(CreateCostCenterAction::class);
        $dto = CreateCostCenterDTO::from([
            'name' => 'Centro de Custo',
            'color' => '#00FF00',
            'operation_type' => 'payable',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('cost_centers', [
            'name' => 'Centro de Custo',
            'operation_type' => 'payable',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateCostCenterAction::class);
        $dto = CreateCostCenterDTO::from([
            'name' => 'Centro Teste',
            'color' => '#FFFFFF',
            'operation_type' => 'receivable',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Centro de custo criado com sucesso.', $result->message);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateCostCenterAction::class);
        $action->execute('not-a-dto');
    }
}
