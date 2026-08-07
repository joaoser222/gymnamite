<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface GatewayInvoiceRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findByInvoice(int $invoiceId): Collection;

    public function findByGatewayAccount(int $gatewayAccountId): Collection;

    public function findByStatus(array $statuses): Collection;
}
