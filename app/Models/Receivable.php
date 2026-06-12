<?php

namespace App\Models;

use App\Enums\OperationType;

class Receivable extends Invoice
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setAttribute('operation_type', OperationType::RECEIVABLE);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setAttribute('operation_type', OperationType::RECEIVABLE);
        });

        static::updating(function ($model) {
            $model->setAttribute('operation_type', OperationType::RECEIVABLE);
        });
    }

    public function setOperationTypeAttribute($value)
    {
        $this->attributes['operation_type'] = OperationType::RECEIVABLE;
    }

    public function getOperationTypeAttribute()
    {
        return OperationType::RECEIVABLE;
    }
}
