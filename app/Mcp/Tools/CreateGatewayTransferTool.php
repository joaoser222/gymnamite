<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\GatewayTransfers\CreateGatewayTransferAction;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('create-gateway-transfer')]
#[Description('Cria uma transferência para uma conta bancária via gateway')]
#[IsIdempotent(false)]
class CreateGatewayTransferTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateGatewayTransferAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'gateway_transfer_recipient_id' => 'required|integer|min:1',
            'value' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $transfer = $this->action->execute($validated);

            return Response::json([
                'id' => $transfer->id,
                'value' => $transfer->value,
                'status' => $transfer->status,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao criar transferência: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('gateway_transfers.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'gateway_transfer_recipient_id' => $schema->integer()->description('ID do destinatário da transferência')->required(),
            'value' => $schema->number()->description('Valor da transferência')->required(),
            'description' => $schema->string()->description('Descrição da transferência')->nullable(),
        ];
    }
}
