<?php

namespace App\PaymentGateways\Definitions;

class AsaasPaymentGatewayDefinition extends PaymentGatewayDefinition
{
    public function name(): string
    {
        return 'Asaas';
    }

    public function description(): string
    {
        return 'Asaas Payment Gateway - Gestão de cobranças, assinaturas e transferências.';
    }

    public function settings(): array
    {
        return [
            new PaymentGatewaySettingDefinition(
                key: 'api_key',
                label: 'API Key',
                type: 'password',
                required: true,
                placeholder: 'Token de integração',
                helpText: 'Chave de API fornecida pelo Asaas em Integração > Chave de API.',
            ),
            new PaymentGatewaySettingDefinition(
                key: 'base_url',
                label: 'Ambiente',
                type: 'select',
                required: true,
                default: 'https://sandbox.asaas.com/api/v3',
                options: [
                    ['value' => 'https://sandbox.asaas.com/api/v3', 'label' => 'Sandbox'],
                    ['value' => 'https://api.asaas.com/api/v3', 'label' => 'Produção'],
                ],
                helpText: 'URL base da API do Asaas.',
            ),
            new PaymentGatewaySettingDefinition(
                key: 'wallet_id',
                label: 'ID da Carteira',
                type: 'string',
                required: false,
                placeholder: 'wallet_...',
                helpText: 'Obrigatório apenas para transferências. Encontrado em Configurações > Carteiras.',
            ),
        ];
    }
}
