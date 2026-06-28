<?php

namespace App\Models;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'status' => BillableStatus::OPEN,
        'payment_method' => PaymentMethod::CASH,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
