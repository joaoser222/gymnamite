<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasVisibility;

    protected $table = 'sales';

    protected $fillable = [
        'total',
        'gross_value',
        'discount_value',
        'status',
        'payment_method',
        'annotations',
        'disable_stock',
        'client_id',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'gross_value' => 'float',
        'total' => 'float',
        'payment_method' => PaymentMethod::class,
    ];

    protected $attributes = [
        'payment_method' => PaymentMethod::CASH,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
