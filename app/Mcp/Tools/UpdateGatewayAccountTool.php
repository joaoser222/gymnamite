<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\GatewayAccounts\UpdateGatewayAccountAction;
use App\DTOs\GatewayAccounts\UpdateGatewayAccountDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-gateway-account')]
#[Description('Atualiza uma conta de gateway de pagamento existente')]
#[IsIdempotent(true)]
class UpdateGatewayAccountTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateGatewayAccountAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'settings' => 'nullable|array',
            'invoicing_enabled' => 'nullable|boolean',
        ]);

        $dto = UpdateGatewayAccountDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'invoicing_enabled' => $result->data->invoicing_enabled,
            'invoicing_supported' => $result->data->invoicing_supported,
            'invoicing_configured' => $result->data->invoicing_configured,
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
            'name' => $schema->string()->description('Nome da conta de gateway')->nullable(),
            'description' => $schema->string()->description('Descrição da conta de gateway')->nullable(),
            'settings' => $schema->array()->description('Configurações do gateway')->nullable(),
            'invoicing_enabled' => $schema->boolean()->description('Habilitar emissão de notas fiscais')->nullable(),
        ];
    }
}
