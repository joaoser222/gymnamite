<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CouponRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Model;

    public function findActive(): Collection;
}
