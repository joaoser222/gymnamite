<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class BaseEloquentRepository implements RepositoryInterface
{
    abstract protected function modelClass(): string;

    protected array $with = [];

    protected array $scopes = [];

    protected string $defaultSort = 'id';

    protected string $defaultSortDirection = 'desc';

    protected array $searchableFields = [];

    protected array $filterableFields = [];

    public function newQuery(): Builder
    {
        $query = call_user_func([$this->modelClass(), 'query']);

        foreach ($this->scopes as $scope) {
            $query->{$scope}();
        }

        $relations = $this->existingRelations($this->with);

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query;
    }

    public function find(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    /**
     * @param  array<int, string>  $relations
     * @return array<int, string>
     */
    protected function existingRelations(array $relations): array
    {
        if ($relations === []) {
            return [];
        }

        $instance = new ($this->modelClass());

        return array_values(array_filter(
            $relations,
            fn (string $relation): bool => method_exists($instance, $relation),
        ));
    }

    public function all(array $filters = []): Collection
    {
        return $this->applyFilters($this->newQuery(), $filters)->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->newQuery(), $filters);
        $query = $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $model = new ($this->modelClass());
            $model->fill($data);
            $model->save();

            return $model->refresh();
        });
    }

    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $model->fill($data);
            $model->save();

            return $model->refresh();
        });
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteByIds(array $ids): int
    {
        return $this->newQuery()->whereIn('id', $ids)->delete();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $search = $filters['search'] ?? $filters['search_field'] ?? null;
        $searchField = $filters['searchField'] ?? $filters['search_field'] ?? null;

        if ($search && $searchField && in_array($searchField, $this->searchableFields, true)) {
            $query->where($searchField, 'like', "%{$search}%");
        } elseif ($search && ! empty($this->searchableFields)) {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        foreach ($this->filterableFields as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        $visibility = $filters['visibility'] ?? 'visible';
        if ($visibility !== 'all' && in_array('visibility', $this->filterableFields, true)) {
            $query->where('visibility', $visibility);
        }

        return $query;
    }

    protected function applySorting(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sortBy'] ?? $filters['sort_by'] ?? $this->defaultSort;
        $sortDirection = $filters['sortDirection'] ?? $filters['sort_direction'] ?? $this->defaultSortDirection;

        $allowedSorts = array_merge($this->searchableFields, $this->filterableFields, ['id', 'created_at', 'updated_at']);

        if (in_array($sortBy, $allowedSorts, true) && in_array(strtolower($sortDirection), ['asc', 'desc'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function with(array $relations): static
    {
        $clone = clone $this;
        $clone->with = $relations;

        return $clone;
    }

    public function applyScopes(array $scopes): static
    {
        $clone = clone $this;
        $clone->scopes = $scopes;

        return $clone;
    }

    public function firstWhere(string $column, mixed $value): ?Model
    {
        return $this->newQuery()->where($column, $value)->first();
    }

    public function firstWhereOrFail(string $column, mixed $value): Model
    {
        return $this->newQuery()->where($column, $value)->firstOrFail();
    }

    public function existsWhere(array $conditions): bool
    {
        return $this->newQuery()->where($conditions)->exists();
    }

    public function count(array $filters = []): int
    {
        return $this->applyFilters($this->newQuery(), $filters)->count();
    }
}
