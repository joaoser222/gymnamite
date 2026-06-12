<?php

namespace App\Models;

use App\Enums\BillableStatus;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class DirectLesson extends Model
{
    use HasVisibility;

    protected $table = 'direct_lessons';

    protected $fillable = [
        'lesson_date',
        'status',
        'price',
        'client_id',
        'trainer_id',
    ];

    protected $casts = [
        'price' => 'float',
        'lesson_date' => 'date',
        'status' => BillableStatus::class,
    ];

    protected $attributes = [
        'status' => BillableStatus::OPEN,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}
