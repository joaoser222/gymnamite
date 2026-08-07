<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface SaleRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findByClient(int $clientId): Collection;

    public function findPendingInvoices(): Collection;
}
