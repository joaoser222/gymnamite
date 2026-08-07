<?php

namespace App\Repositories\Eloquent;

use App\Models\PlanCategory;
use App\Repositories\Contracts\PlanCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentPlanCategoryRepository extends BaseEloquentRepository implements PlanCategoryRepositoryInterface
{
    protected function modelClass(): string
    {
        return PlanCategory::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findActive(): Collection
    {
        return $this->newQuery()->where('visibility', 'visible')->get();
    }
}
