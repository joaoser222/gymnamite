<?php

namespace App\Repositories\Eloquent;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentSupplierRepository extends BaseEloquentRepository implements SupplierRepositoryInterface
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name', 'document'];

    protected array $filterableFields = ['visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findByDocument(string $document): ?Model
    {
        return $this->newQuery()->where('document', $document)->first();
    }
}
