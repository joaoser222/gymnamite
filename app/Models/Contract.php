<?php

namespace App\Models;

use App\Enums\BillableStatus;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasVisibility;

    protected $table = 'contracts';

    protected $fillable = [
        'plan_name',
        'modality_quantity',
        'price',
        'start_date',
        'duration',
        'accepted_terms',
        'annotations',
        'plan_id',
        'plan_category_id',
        'client_id',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'price' => 'float',
        'status' => BillableStatus::class,
    ];

    protected $attributes = [
        'status' => BillableStatus::OPEN,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function planCategory()
    {
        return $this->belongsTo(PlanCategory::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
