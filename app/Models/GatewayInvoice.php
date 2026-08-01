<?php

namespace App\Models;

use App\Enums\Gateway\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayInvoice extends Model
{
    protected $fillable = [
        'gateway_reference_key', 'status', 'status_description', 'invoice_number',
        'validation_code', 'service_description', 'observations', 'value', 'deductions',
        'effective_date', 'pdf_url', 'xml_url', 'municipal_service_id',
        'municipal_service_code', 'municipal_service_description', 'external_reference',
        'payload', 'error_message',
        'gateway_account_id', 'gateway_payment_id', 'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => InvoiceStatus::class,
            'value' => 'float',
            'deductions' => 'float',
            'effective_date' => 'date:Y-m-d',
        ];
    }

    public function gatewayAccount(): BelongsTo
    {
        return $this->belongsTo(GatewayAccount::class);
    }

    public function gatewayPayment(): BelongsTo
    {
        return $this->belongsTo(GatewayPayment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
