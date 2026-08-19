<?php

namespace App\Repositories\Eloquent;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentSaleRepository extends BaseEloquentRepository implements SaleRepositoryInterface
{
    protected function modelClass(): string
    {
        return Sale::class;
    }

    protected array $with = ['client', 'items.product'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['client_id', 'status', 'payment_method', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findByClient(int $clientId): Collection
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
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
