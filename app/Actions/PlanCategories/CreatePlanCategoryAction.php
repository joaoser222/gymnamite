<?php

namespace App\Actions\PlanCategories;

use App\Actions\BaseAction;
use App\DTOs\PlanCategories\ActionResultDTO;
use App\DTOs\PlanCategories\CreatePlanCategoryDTO;
use App\Models\PlanCategory;
use App\Repositories\Contracts\PlanCategoryRepositoryInterface;

class CreatePlanCategoryAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = PlanCategory::class;

    public function __construct(
        private readonly PlanCategoryRepositoryInterface $planCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreatePlanCategoryDTO) {
            throw new \InvalidArgumentException('CreatePlanCategoryAction requires a CreatePlanCategoryDTO.');
        }

        $dto = $input;
        $planCategory = $this->planCategoryRepository->create([
            'name' => $dto->name,
        ]);

        return ActionResultDTO::success(
            $planCategory,
            'Categoria de plano criada com sucesso.'
        );
    }
}
