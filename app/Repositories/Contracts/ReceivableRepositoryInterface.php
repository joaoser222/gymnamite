<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ReceivableRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findPending(): Collection;

    public function findOverdue(): Collection;

    public function findByHolder(int $holderId): Collection;
}
