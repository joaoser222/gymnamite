<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';

    protected $fillable = [
        'product_name',
        'price',
        'quantity',
        'total',
        'product_id',
        'sale_id',
    ];

    protected $casts = [
        'price' => 'float',
        'total' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
