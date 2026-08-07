<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PayableRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findPending(): Collection;

    public function findOverdue(): Collection;

    public function findBySupplier(int $supplierId): Collection;
}
