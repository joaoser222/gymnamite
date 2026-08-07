<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface PlanCategoryRepositoryInterface extends RepositoryInterface
{
    public function findActive(): Collection;
}
