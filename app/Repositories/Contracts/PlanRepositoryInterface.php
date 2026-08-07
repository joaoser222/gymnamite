<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PlanRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findActive(): Collection;

    public function findByCategory(int $categoryId): Collection;
}
