<?php

namespace App\Repositories\Eloquent;

use App\Models\Receivable;
use App\Repositories\Contracts\ReceivableRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentReceivableRepository extends BaseEloquentRepository implements ReceivableRepositoryInterface
{
    protected function modelClass(): string
    {
        return Receivable::class;
    }

    protected array $with = ['holder', 'gatewayPayment', 'gatewayInvoice', 'financialAccount', 'financialCategory'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['holder_id', 'status', 'financial_account_id', 'financial_category_id', 'operation_type', 'visibility'];

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

    public function findByHolder(int $holderId): Collection
    {
        return $this->newQuery()
            ->where('holder_id', $holderId)
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
