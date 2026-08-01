<?php

namespace App\Models;

use App\PaymentGateways\PaymentGatewayManager;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class GatewayAccount extends Model
{
    use HasVisibility;

    protected $table = 'gateway_accounts';

    protected $fillable = [
        'name',
        'description',
        'invoicing_enabled',
        'invoicing_supported',
        'invoicing_configured',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'invoicing_enabled' => 'boolean',
        'invoicing_supported' => 'boolean',
        'invoicing_configured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (GatewayAccount $account): void {
            $definition = app(PaymentGatewayManager::class)->find((string) $account->name);
            $account->invoicing_supported = $definition?->supportsInvoicing() === true;

            // A configuração fiscal é considerada válida quando o emissor possui os
            // dados municipais mínimos (descrição do serviço e código de serviço).
            // O marcador settings.invoicing.fiscal_configuration_at indica que a
            // configuração foi efetivada no provedor (PUT /invoices/municipalConfiguration).
            $account->invoicing_configured = $account->invoicing_supported
                && filled(data_get($account->settings, 'invoicing.service_description'))
                && filled(data_get($account->settings, 'invoicing.municipal_service_code'));
        });
    }

    public function customers()
    {
        return $this->hasMany(GatewayCustomer::class);
    }
}
