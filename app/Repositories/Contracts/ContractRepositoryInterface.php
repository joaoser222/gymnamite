<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ContractRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findEligibleForGatewaySync(array $filters = []): Collection;

    public function findByClient(int $clientId): Collection;

    public function findActiveByClient(int $clientId): ?Model;

    public function countByStatus(string $status): int;
}
