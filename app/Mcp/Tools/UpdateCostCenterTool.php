<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CostCenters\UpdateCostCenterAction;
use App\DTOs\CostCenters\UpdateCostCenterDTO;
use App\Models\CostCenter;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-cost-center')]
#[Description('Atualiza um centro de custo existente')]
#[IsIdempotent(true)]
class UpdateCostCenterTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateCostCenterAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'operation_type' => 'nullable|in:entry,exit',
        ]);

        $costCenter = CostCenter::find($validated['id']);

        if (! $costCenter) {
            return Response::error('Centro de custo não encontrado.');
        }

        $dto = UpdateCostCenterDTO::from(array_merge(
            ['id' => $costCenter->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

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
        return auth()->user()?->can('cost_centers.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do centro de custo')->required(),
            'name' => $schema->string()->description('Novo nome do centro de custo')->nullable(),
            'color' => $schema->string()->description('Nova cor do centro de custo (hex, ex: #FF0000)')->nullable(),
            'operation_type' => $schema->string()->description('Novo tipo de operação (entry ou exit)')->nullable(),
        ];
    }
}
