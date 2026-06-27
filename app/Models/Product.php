<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasVisibility;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'purchase_price',
        'sale_price',
        'quantity',
        'product_type',
        'product_unity',
    ];

    protected $casts = [
        'purchase_price' => 'float',
        'sale_price' => 'float',
        'product_type' => ProductType::class,
    ];

    protected $attributes = [
        'product_type' => ProductType::MERCHANDISE,
    ];

    public function productUnity()
    {
        return $this->belongsTo(ProductUnity::class, 'product_unity', 'code');
    }
}
