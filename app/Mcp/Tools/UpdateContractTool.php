<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Contracts\UpdateContractAction;
use App\DTOs\Contracts\UpdateContractDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Contract;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-contract')]
#[Description('Atualiza um contrato existente')]
#[IsIdempotent(true)]
class UpdateContractTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateContractAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'annotations' => 'nullable|string|max:500',
        ]);

        $contract = Contract::find($validated['id']);

        if (! $contract) {
            return Response::error('Contrato não encontrado.');
        }

        $dto = UpdateContractDTO::from(array_merge(
            ['id' => $contract->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'plan_name' => $result->data->plan->name ?? null,
            'status' => $result->data->status,
            'total' => $result->data->total,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('contracts.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do contrato')->required(),
            'annotations' => $schema->string()->description('Observações do contrato')->nullable(),
        ];
    }
}
