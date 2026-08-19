<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CostCenterRepositoryInterface extends RepositoryInterface
{
    public function findActive(): Collection;
}
