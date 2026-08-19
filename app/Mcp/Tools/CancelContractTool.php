<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Contracts\CancelContractAction;
use App\DTOs\Contracts\CancelContractDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Contract;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('cancel-contract')]
#[Description('Cancela um contrato existente')]
#[IsIdempotent(false)]
class CancelContractTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CancelContractAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'contract_id' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $contract = Contract::find($validated['contract_id']);

        if (! $contract) {
            return Response::error('Contrato não encontrado.');
        }

        $dto = CancelContractDTO::from([
            'contract_id' => $contract->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $contract->id,
            'status' => 'canceled',
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('contracts.cancel') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'contract_id' => $schema->integer()->description('ID do contrato a ser cancelado')->required(),
            'reason' => $schema->string()->description('Motivo do cancelamento')->nullable(),
        ];
    }
}
