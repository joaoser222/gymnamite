<?php

namespace App\Repositories\Eloquent;

use App\Models\Movement;
use App\Repositories\Contracts\MovementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentMovementRepository extends BaseEloquentRepository implements MovementRepositoryInterface
{
    protected function modelClass(): string
    {
        return Movement::class;
    }

    protected array $with = ['financialAccount', 'payable', 'receivable', 'transfer'];

    protected array $searchableFields = ['type'];

    protected array $filterableFields = ['financial_account_id', 'type', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findByFinancialAccount(int $financialAccountId): Collection
    {
        return $this->newQuery()
            ->where('financial_account_id', $financialAccountId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->newQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
