<?php

namespace App\Repositories\Eloquent;

use App\Models\GatewayPayment;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentGatewayPaymentRepository extends BaseEloquentRepository implements GatewayPaymentRepositoryInterface
{
    protected function modelClass(): string
    {
        return GatewayPayment::class;
    }

    protected array $with = ['gatewayAccount', 'invoice.holder'];

    protected array $searchableFields = ['external_id', 'status'];

    protected array $filterableFields = ['gateway_account_id', 'status', 'payment_method', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->newQuery()->where('invoice_id', $invoiceId)->get();
    }

    public function findPendingByAccount(int $gatewayAccountId): Collection
    {
        return $this->newQuery()
            ->where('gateway_account_id', $gatewayAccountId)
            ->where('status', 'pending')
            ->get();
    }
}
