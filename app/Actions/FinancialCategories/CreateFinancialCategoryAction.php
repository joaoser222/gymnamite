<?php

namespace App\Actions\FinancialCategories;

use App\Actions\BaseAction;
use App\DTOs\FinancialCategories\ActionResultDTO;
use App\DTOs\FinancialCategories\CreateFinancialCategoryDTO;
use App\Models\FinancialCategory;
use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;

class CreateFinancialCategoryAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = FinancialCategory::class;

    public function __construct(
        private readonly FinancialCategoryRepositoryInterface $financialCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateFinancialCategoryDTO) {
            throw new \InvalidArgumentException('CreateFinancialCategoryAction requires a CreateFinancialCategoryDTO.');
        }

        $dto = $input;
        $category = $this->financialCategoryRepository->create([
            'name' => $dto->name,
            'color' => $dto->color,
            'operation_type' => $dto->operation_type,
            'cost_center_id' => $dto->cost_center_id,
        ]);

        return ActionResultDTO::success(
            $category,
            'Categoria financeira criada com sucesso.'
        );
    }
}
