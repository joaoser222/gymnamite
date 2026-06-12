<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Enums\OperationType;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    protected $fillable = [
        'operation_type',
        'movement_type',
        'value',
        'invoice_id',
    ];

    protected $casts = [
        'value' => 'float',
        'operation_type' => OperationType::class,
        'movement_type' => MovementType::class,
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
