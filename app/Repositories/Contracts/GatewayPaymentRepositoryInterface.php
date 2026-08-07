<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface GatewayPaymentRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findByInvoice(int $invoiceId): Collection;

    public function findPendingByAccount(int $gatewayAccountId): Collection;
}
