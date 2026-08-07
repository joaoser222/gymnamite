<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentCouponRepository extends BaseEloquentRepository implements CouponRepositoryInterface
{
    protected function modelClass(): string
    {
        return Coupon::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['code'];

    protected array $filterableFields = ['visibility'];

    protected string $defaultSort = 'code';

    protected string $defaultSortDirection = 'asc';

    public function findByCode(string $code): ?Model
    {
        return $this->newQuery()->where('code', $code)->first();
    }

    public function findActive(): Collection
    {
        return $this->newQuery()
            ->where('visibility', 'visible')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            })
            ->get();
    }
}
