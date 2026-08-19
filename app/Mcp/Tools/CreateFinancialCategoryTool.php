<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\FinancialCategories\CreateFinancialCategoryAction;
use App\DTOs\FinancialCategories\CreateFinancialCategoryDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-financial-category')]
#[Description('Cria uma nova categoria financeira')]
#[IsIdempotent(false)]
class CreateFinancialCategoryTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateFinancialCategoryAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'operation_type' => 'required|in:entry,exit',
            'cost_center_id' => 'nullable|integer|min:1',
        ]);

        $dto = CreateFinancialCategoryDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'color' => $result->data->color,
            'operation_type' => $result->data->operation_type,
            'cost_center_id' => $result->data->cost_center_id,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('financial_categories.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome da categoria financeira')->required(),
            'color' => $schema->string()->description('Cor da categoria financeira (hex, ex: #FF0000)')->nullable(),
            'operation_type' => $schema->string()->description('Tipo de operação (entry ou exit)')->required(),
            'cost_center_id' => $schema->integer()->description('ID do centro de custo associado')->nullable(),
        ];
    }
}
