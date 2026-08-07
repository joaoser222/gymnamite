<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface MovementRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findByFinancialAccount(int $financialAccountId): Collection;

    public function findByDateRange(string $startDate, string $endDate): Collection;
}
