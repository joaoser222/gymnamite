<?php

namespace App\Actions\CostCenters;

use App\Actions\BaseAction;
use App\DTOs\CostCenters\ActionResultDTO;
use App\DTOs\CostCenters\CreateCostCenterDTO;
use App\Models\CostCenter;
use App\Repositories\Contracts\CostCenterRepositoryInterface;

class CreateCostCenterAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = CostCenter::class;

    public function __construct(
        private readonly CostCenterRepositoryInterface $costCenterRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateCostCenterDTO) {
            throw new \InvalidArgumentException('CreateCostCenterAction requires a CreateCostCenterDTO.');
        }

        $dto = $input;
        $costCenter = $this->costCenterRepository->create([
            'name' => $dto->name,
            'color' => $dto->color,
            'operation_type' => $dto->operation_type,
        ]);

        return ActionResultDTO::success(
            $costCenter,
            'Centro de custo criado com sucesso.'
        );
    }
}
