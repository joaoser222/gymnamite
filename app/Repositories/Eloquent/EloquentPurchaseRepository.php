<?php

namespace App\Repositories\Eloquent;

use App\Models\Purchase;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentPurchaseRepository extends BaseEloquentRepository implements PurchaseRepositoryInterface
{
    protected function modelClass(): string
    {
        return Purchase::class;
    }

    protected array $with = ['supplier', 'items.product'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['supplier_id', 'status', 'payment_method', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findBySupplier(int $supplierId): Collection
    {
        return $this->newQuery()
            ->where('supplier_id', $supplierId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findPendingInvoices(): Collection
    {
        return $this->newQuery()
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'pending');
            })
            ->get();
    }
}
