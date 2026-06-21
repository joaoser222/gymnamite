<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasVisibility;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'percent',
        'discount_limit',
        'duration',
        'expiration_date',
    ];

    protected $casts = [
        'expiration_date'  => 'date:Y-m-d',
        'percent' => 'float',
        'discount_limit' => 'float',
    ];
}
