<?php

namespace App\Models;

use App\Enums\OperationType;

class Payable extends Invoice
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setAttribute('operation_type', OperationType::PAYABLE);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setAttribute('operation_type', OperationType::PAYABLE);
        });

        static::updating(function ($model) {
            $model->setAttribute('operation_type', OperationType::PAYABLE);
        });
    }

    public function setOperationTypeAttribute($value)
    {
        $this->attributes['operation_type'] = OperationType::PAYABLE;
    }

    public function getOperationTypeAttribute()
    {
        return OperationType::PAYABLE;
    }
}
