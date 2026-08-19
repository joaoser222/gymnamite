<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\GatewayAccounts\ConfigureFiscalDataAction;
use App\DTOs\GatewayAccounts\ConfigureFiscalDataDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('configure-fiscal-data')]
#[Description('Configura dados fiscais de uma conta de gateway')]
#[IsIdempotent(true)]
class ConfigureFiscalDataTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected ConfigureFiscalDataAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'municipal_service_code' => 'required|string|max:100',
            'service_description' => 'required|string|max:5000',
            'municipal_service_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string|max:5000',
            'incentivized_tax' => 'nullable|boolean',
        ]);

        $dto = ConfigureFiscalDataDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data['id'] ?? $result->data->id ?? null,
            'invoicing_configured' => $result->data['invoicing_configured'] ?? $result->data->invoicing_configured ?? null,
            'invoicing_supported' => $result->data['invoicing_supported'] ?? $result->data->invoicing_supported ?? null,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('gateway_accounts.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da conta de gateway')->required(),
            'municipal_service_code' => $schema->string()->description('Código do serviço municipal')->required(),
            'service_description' => $schema->string()->description('Descrição do serviço')->required(),
            'municipal_service_name' => $schema->string()->description('Nome do serviço municipal')->nullable(),
            'observations' => $schema->string()->description('Observações')->nullable(),
            'incentivized_tax' => $schema->boolean()->description('Indica se o serviço é incentivado (isenção de tributos)')->nullable(),
        ];
    }
}
