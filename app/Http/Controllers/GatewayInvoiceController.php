<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\Gateway\InvoiceStatus;
use App\Models\GatewayInvoice;
use Illuminate\Http\Request;

class GatewayInvoiceController extends ReadOnlyModuleController
{
    protected array $fields = [
        'id', 'gateway_reference_key', 'status', 'status_description', 'gateway_account_id',
        'gateway_payment_id', 'invoice_id', 'invoice_number', 'validation_code', 'service_description',
        'observations', 'value', 'deductions', 'effective_date', 'pdf_url', 'xml_url',
        'municipal_service_id', 'municipal_service_code', 'municipal_service_description',
        'external_reference', 'created_at',
    ];

    protected array $searchableFields = ['gateway_reference_key', 'status', 'invoice_number', 'external_reference'];

    protected array $sortableFields = ['id', 'status', 'value', 'effective_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_INVOICE;
    }

    protected function modelClass(): string
    {
        return GatewayInvoice::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return ['options' => ['invoiceStatus' => $this->enumOptions(InvoiceStatus::class)]];
    }
}
