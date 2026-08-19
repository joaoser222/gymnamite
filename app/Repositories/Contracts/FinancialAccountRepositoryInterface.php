<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface FinancialAccountRepositoryInterface extends RepositoryInterface
{
    public function findActive(): Collection;

    public function findByType(string $type): Collection;
}
