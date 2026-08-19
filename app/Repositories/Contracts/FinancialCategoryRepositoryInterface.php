<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface FinancialCategoryRepositoryInterface extends RepositoryInterface
{
    public function findByCostCenter(int $costCenterId): Collection;

    public function findActive(): Collection;
}
