<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentInvoiceRepository extends BaseEloquentRepository implements InvoiceRepositoryInterface
{
    protected function modelClass(): string
    {
        return Invoice::class;
    }

    protected array $with = ['holder', 'billable', 'gatewayPayment', 'financialAccount', 'financialCategory'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['holder_id', 'billable_id', 'billable_type', 'status', 'operation_type', 'visibility', 'financial_account_id', 'financial_category_id'];

    protected string $defaultSort = 'due_date';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findEligibleForGatewaySync(array $filters = []): Collection
    {
        return $this->newQuery()
            ->where('operation_type', 'receivable')
            ->where('uses_gateway_payment_method', true)
            ->where('should_generate_gateway_transaction', true)
            ->where('status', 'pending')
            ->whereHas('gatewayPayment', function ($query) {
                $query->where('status', 'pending');
            })
            ->get();
    }

    public function findPendingByHolder(int $holderId): Collection
    {
        return $this->newQuery()
            ->where('holder_id', $holderId)
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function findByBillable(int $billableId, string $billableType): Collection
    {
        return $this->newQuery()
            ->where('billable_id', $billableId)
            ->where('billable_type', $billableType)
            ->orderBy('installment_number', 'asc')
            ->get();
    }

    public function countByStatus(string $status): int
    {
        return $this->newQuery()->where('status', $status)->count();
    }

    public function findForFiscalSync(array $accountIds = [], array $statuses = []): Collection
    {
        $query = $this->newQuery()
            ->whereHas('gatewayPayment', function ($q) use ($accountIds) {
                if (! empty($accountIds)) {
                    $q->whereIn('gateway_account_id', $accountIds);
                }
            });

        if (! empty($statuses)) {
            $query->whereHas('gatewayInvoice', function ($q) use ($statuses) {
                $q->whereIn('status', $statuses);
            });
        }

        return $query->get();
    }
}
