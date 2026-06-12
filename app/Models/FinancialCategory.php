<?php

namespace App\Models;

use App\Enums\OperationType;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    use HasVisibility;

    protected $table = 'financial_categories';

    protected $fillable = [
        'name',
        'color',
        'operation_type',
        'cost_center_id',
    ];

    protected $casts = [
        'operation_type' => OperationType::class,
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
