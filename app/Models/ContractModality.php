<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractModality extends Model
{
    protected $table = 'contract_modalities';

    protected $fillable = [
        'contract_id',
        'modality_id',
        'week_days',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }
}
