<?php

namespace App\Repositories\Eloquent;

use App\Models\GatewayInvoice;
use App\Repositories\Contracts\GatewayInvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentGatewayInvoiceRepository extends BaseEloquentRepository implements GatewayInvoiceRepositoryInterface
{
    protected function modelClass(): string
    {
        return GatewayInvoice::class;
    }

    protected array $with = ['gatewayAccount', 'gatewayPayment.invoice.holder'];

    protected array $searchableFields = ['external_id', 'status'];

    protected array $filterableFields = ['gateway_account_id', 'gateway_payment_id', 'status', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->newQuery()
            ->whereHas('gatewayPayment', function ($query) use ($invoiceId) {
                $query->where('invoice_id', $invoiceId);
            })
            ->get();
    }

    public function findByGatewayAccount(int $gatewayAccountId): Collection
    {
        return $this->newQuery()->where('gateway_account_id', $gatewayAccountId)->get();
    }

    public function findByStatus(array $statuses): Collection
    {
        return $this->newQuery()->whereIn('status', $statuses)->get();
    }
}
