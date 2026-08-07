<?php

namespace App\Repositories\Eloquent;

use App\Models\Payable;
use App\Repositories\Contracts\PayableRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentPayableRepository extends BaseEloquentRepository implements PayableRepositoryInterface
{
    protected function modelClass(): string
    {
        return Payable::class;
    }

    protected array $with = ['supplier', 'financialAccount', 'financialCategory'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['supplier_id', 'status', 'financial_account_id', 'financial_category_id', 'operation_type', 'visibility'];

    protected string $defaultSort = 'due_date';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findPending(): Collection
    {
        return $this->newQuery()->where('status', 'pending')->orderBy('due_date', 'asc')->get();
    }

    public function findOverdue(): Collection
    {
        return $this->newQuery()
            ->where('status', 'pending')
            ->where('due_date', '<', now()->format('Y-m-d'))
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function findBySupplier(int $supplierId): Collection
    {
        return $this->newQuery()
            ->where('supplier_id', $supplierId)
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
