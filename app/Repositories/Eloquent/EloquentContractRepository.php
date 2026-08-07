<?php

namespace App\Repositories\Eloquent;

use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentContractRepository extends BaseEloquentRepository implements ContractRepositoryInterface
{
    protected function modelClass(): string
    {
        return Contract::class;
    }

    protected array $with = ['client', 'plan', 'coupon'];

    protected array $searchableFields = ['plan_name', 'status'];

    protected array $filterableFields = ['client_id', 'plan_id', 'status', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findEligibleForGatewaySync(array $filters = []): Collection
    {
        return $this->newQuery()
            ->where('payment_method', '!=', 'cash')
            ->where('accepted_terms', 'accepted')
            ->whereHas('invoices', function ($query) {
                $query->where('uses_gateway_payment_method', true)
                    ->where('should_generate_gateway_transaction', true);
            })
            ->get();
    }

    public function findByClient(int $clientId): Collection
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findActiveByClient(int $clientId): ?Model
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function countByStatus(string $status): int
    {
        return $this->newQuery()->where('status', $status)->count();
    }
}
