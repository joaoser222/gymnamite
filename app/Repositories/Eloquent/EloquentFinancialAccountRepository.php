<?php

namespace App\Repositories\Eloquent;

use App\Models\FinancialAccount;
use App\Repositories\Contracts\FinancialAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentFinancialAccountRepository extends BaseEloquentRepository implements FinancialAccountRepositoryInterface
{
    protected function modelClass(): string
    {
        return FinancialAccount::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['account_type', 'visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findActive(): Collection
    {
        return $this->newQuery()->where('visibility', 'visible')->get();
    }

    public function findByType(string $type): Collection
    {
        return $this->newQuery()
            ->where('account_type', $type)
            ->get();
    }
}
