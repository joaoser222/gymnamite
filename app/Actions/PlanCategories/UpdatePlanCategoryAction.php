<?php

namespace App\Actions\PlanCategories;

use App\Actions\BaseAction;
use App\DTOs\PlanCategories\ActionResultDTO;
use App\DTOs\PlanCategories\UpdatePlanCategoryDTO;
use App\Models\PlanCategory;
use App\Repositories\Contracts\PlanCategoryRepositoryInterface;

class UpdatePlanCategoryAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = PlanCategory::class;

    public function __construct(
        private readonly PlanCategoryRepositoryInterface $planCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdatePlanCategoryDTO) {
            throw new \InvalidArgumentException('UpdatePlanCategoryAction requires an UpdatePlanCategoryDTO.');
        }

        $dto = $input;
        $planCategory = $this->planCategoryRepository->findOrFail($dto->id);

        $updateData = array_filter([
            'name' => $dto->name,
        ], fn ($value) => $value !== null);

        $this->planCategoryRepository->update($planCategory, $updateData);

        return ActionResultDTO::success(
            $planCategory->refresh(),
            'Categoria de plano atualizada com sucesso.'
        );
    }
}
