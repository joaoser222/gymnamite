<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GatewayTransferRecipient extends Model
{
    use HasVisibility;

    protected $fillable = [
        'gateway_account_id',
        'label',
        'holder_name',
        'holder_document',
        'pix_key',
        'pix_key_type',
    ];

    protected function casts(): array
    {
        return ['pix_key' => 'encrypted'];
    }

    public function gatewayAccount(): BelongsTo
    {
        return $this->belongsTo(GatewayAccount::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(GatewayTransfer::class);
    }
}
