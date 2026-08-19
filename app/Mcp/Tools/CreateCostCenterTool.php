<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CostCenters\CreateCostCenterAction;
use App\DTOs\CostCenters\CreateCostCenterDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-cost-center')]
#[Description('Cria um novo centro de custo')]
#[IsIdempotent(false)]
class CreateCostCenterTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateCostCenterAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'operation_type' => 'required|in:entry,exit',
        ]);

        $dto = CreateCostCenterDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'color' => $result->data->color,
            'operation_type' => $result->data->operation_type,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('cost_centers.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome do centro de custo')->required(),
            'color' => $schema->string()->description('Cor do centro de custo (hex, ex: #FF0000)')->nullable(),
            'operation_type' => $schema->string()->description('Tipo de operação (entry ou exit)')->required(),
        ];
    }
}
