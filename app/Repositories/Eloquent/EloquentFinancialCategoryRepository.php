<?php

namespace App\Repositories\Eloquent;

use App\Models\FinancialCategory;
use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentFinancialCategoryRepository extends BaseEloquentRepository implements FinancialCategoryRepositoryInterface
{
    protected function modelClass(): string
    {
        return FinancialCategory::class;
    }

    protected array $with = ['costCenter'];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['operation_type', 'cost_center_id', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findByCostCenter(int $costCenterId): Collection
    {
        return $this->newQuery()
            ->where('cost_center_id', $costCenterId)
            ->get();
    }

    public function findActive(): Collection
    {
        return $this->newQuery()->where('visibility', 'visible')->get();
    }
}
