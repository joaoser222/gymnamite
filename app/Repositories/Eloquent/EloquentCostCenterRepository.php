<?php

namespace App\Repositories\Eloquent;

use App\Models\CostCenter;
use App\Repositories\Contracts\CostCenterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCostCenterRepository extends BaseEloquentRepository implements CostCenterRepositoryInterface
{
    protected function modelClass(): string
    {
        return CostCenter::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['operation_type', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findActive(): Collection
    {
        return $this->newQuery()->where('visibility', 'visible')->get();
    }
}
