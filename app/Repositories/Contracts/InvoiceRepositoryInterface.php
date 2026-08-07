<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findEligibleForGatewaySync(array $filters = []): Collection;

    public function findPendingByHolder(int $holderId): Collection;

    public function findByBillable(int $billableId, string $billableType): Collection;

    public function countByStatus(string $status): int;

    public function findForFiscalSync(array $accountIds = [], array $statuses = []): Collection;
}
