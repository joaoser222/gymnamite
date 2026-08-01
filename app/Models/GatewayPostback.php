<?php

namespace App\Models;

use App\Enums\Gateway\PostbackStatus;
use Illuminate\Database\Eloquent\Model;

class GatewayPostback extends Model
{
    protected $fillable = [
        'postback_event',
        'postback_type',
        'external_event_key',
        'payload',
        'status',
        'gateway_account_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => PostbackStatus::class,
        ];
    }

    //
}
