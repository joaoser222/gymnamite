<?php

namespace App\Actions\CostCenters;

use App\Actions\BaseAction;
use App\DTOs\CostCenters\ActionResultDTO;
use App\DTOs\CostCenters\UpdateCostCenterDTO;
use App\Models\CostCenter;
use App\Repositories\Contracts\CostCenterRepositoryInterface;

class UpdateCostCenterAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = CostCenter::class;

    public function __construct(
        private readonly CostCenterRepositoryInterface $costCenterRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateCostCenterDTO) {
            throw new \InvalidArgumentException('UpdateCostCenterAction requires an UpdateCostCenterDTO.');
        }

        $dto = $input;
        $costCenter = $this->costCenterRepository->findOrFail($dto->id);

        $updateData = array_filter([
            'name' => $dto->name,
            'color' => $dto->color,
            'operation_type' => $dto->operation_type,
        ], fn ($value) => $value !== null);

        $this->costCenterRepository->update($costCenter, $updateData);

        return ActionResultDTO::success(
            $costCenter->refresh(),
            'Centro de custo atualizado com sucesso.'
        );
    }
}
