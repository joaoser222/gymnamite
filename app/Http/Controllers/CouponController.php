<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Coupon;
use App\Traits\HasModule;

class CouponController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'code', 'percent', 'discount_limit', 'duration', 'expiration_date', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['code'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'code', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::COUPON;
    }

    protected function modelClass(): string
    {
        return Coupon::class;
    }
}
