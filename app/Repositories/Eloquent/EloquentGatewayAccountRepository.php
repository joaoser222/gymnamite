<?php

namespace App\Repositories\Eloquent;

use App\Models\GatewayAccount;
use App\Repositories\Contracts\GatewayAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentGatewayAccountRepository extends BaseEloquentRepository implements GatewayAccountRepositoryInterface
{
    protected function modelClass(): string
    {
        return GatewayAccount::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name', 'description'];

    protected array $filterableFields = ['name', 'invoicing_enabled', 'invoicing_supported', 'invoicing_configured', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findEligibleForInvoicing(): Collection
    {
        return $this->newQuery()
            ->where('invoicing_enabled', true)
            ->where('invoicing_supported', true)
            ->where('invoicing_configured', true)
            ->where('visibility', 'visible')
            ->get();
    }

    public function findByProvider(string $providerName): ?Model
    {
        return $this->newQuery()->where('name', $providerName)->first();
    }
}
