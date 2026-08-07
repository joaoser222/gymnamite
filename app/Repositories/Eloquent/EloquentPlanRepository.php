<?php

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentPlanRepository extends BaseEloquentRepository implements PlanRepositoryInterface
{
    protected function modelClass(): string
    {
        return Plan::class;
    }

    protected array $with = ['tiers', 'planCategory', 'modalities'];

    protected array $searchableFields = ['name', 'description'];

    protected array $filterableFields = ['plan_category_id', 'status', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findActive(): Collection
    {
        return $this->newQuery()->where('status', 'active')->where('visibility', 'visible')->get();
    }

    public function findByCategory(int $categoryId): Collection
    {
        return $this->newQuery()->where('plan_category_id', $categoryId)->get();
    }
}
