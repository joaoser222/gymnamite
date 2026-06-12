<?php

namespace App\Models;

use App\Enums\OperationType;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasVisibility;

    protected $table = 'cost_centers';

    protected $fillable = [
        'name',
        'color',
        'operation_type',
    ];

    protected $casts = [
        'operation_type' => OperationType::class,
    ];

    public function financialCategories()
    {
        return $this->hasMany(FinancialCategory::class);
    }
}
