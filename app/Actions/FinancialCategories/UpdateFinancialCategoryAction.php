<?php

namespace App\Actions\FinancialCategories;

use App\Actions\BaseAction;
use App\DTOs\FinancialCategories\ActionResultDTO;
use App\DTOs\FinancialCategories\UpdateFinancialCategoryDTO;
use App\Models\FinancialCategory;
use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;

class UpdateFinancialCategoryAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = FinancialCategory::class;

    public function __construct(
        private readonly FinancialCategoryRepositoryInterface $financialCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateFinancialCategoryDTO) {
            throw new \InvalidArgumentException('UpdateFinancialCategoryAction requires an UpdateFinancialCategoryDTO.');
        }

        $dto = $input;
        $category = $this->financialCategoryRepository->findOrFail($dto->id);

        $updateData = array_filter([
            'name' => $dto->name,
            'color' => $dto->color,
            'operation_type' => $dto->operation_type,
            'cost_center_id' => $dto->cost_center_id,
        ], fn ($value) => $value !== null);

        $this->financialCategoryRepository->update($category, $updateData);

        return ActionResultDTO::success(
            $category->refresh(),
            'Categoria financeira atualizada com sucesso.'
        );
    }
}
