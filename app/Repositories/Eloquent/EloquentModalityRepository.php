<?php

namespace App\Repositories\Eloquent;

use App\Models\Modality;
use App\Repositories\Contracts\ModalityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentModalityRepository extends BaseEloquentRepository implements ModalityRepositoryInterface
{
    protected function modelClass(): string
    {
        return Modality::class;
    }

    protected array $searchableFields = ['name', 'description'];

    protected array $filterableFields = ['status', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findActive(): Collection
    {
        return $this->newQuery()->where('status', 'active')->where('visibility', 'visible')->get();
    }
}
