<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentProductRepository extends BaseEloquentRepository implements ProductRepositoryInterface
{
    protected function modelClass(): string
    {
        return Product::class;
    }

    protected array $with = ['unity'];

    protected array $searchableFields = ['name', 'description', 'sku'];

    protected array $filterableFields = ['category_id', 'unity_id', 'status', 'visibility'];

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
        return $this->newQuery()->where('category_id', $categoryId)->get();
    }
}
