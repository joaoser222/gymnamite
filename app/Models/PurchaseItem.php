<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';

    protected $fillable = [
        'product_name',
        'price',
        'quantity',
        'total',
        'product_id',
        'purchase_id',
    ];

    protected $casts = [
        'price' => 'float',
        'total' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
