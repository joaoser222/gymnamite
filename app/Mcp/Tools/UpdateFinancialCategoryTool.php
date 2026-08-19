<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\FinancialCategories\UpdateFinancialCategoryAction;
use App\DTOs\FinancialCategories\UpdateFinancialCategoryDTO;
use App\Models\FinancialCategory;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-financial-category')]
#[Description('Atualiza uma categoria financeira existente')]
#[IsIdempotent(true)]
class UpdateFinancialCategoryTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateFinancialCategoryAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'operation_type' => 'nullable|in:entry,exit',
            'cost_center_id' => 'nullable|integer|min:1',
        ]);

        $financialCategory = FinancialCategory::find($validated['id']);

        if (! $financialCategory) {
            return Response::error('Categoria financeira não encontrada.');
        }

        $dto = UpdateFinancialCategoryDTO::from(array_merge(
            ['id' => $financialCategory->id],
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
            'cost_center_id' => $result->data->cost_center_id,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('financial_categories.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da categoria financeira')->required(),
            'name' => $schema->string()->description('Novo nome da categoria financeira')->nullable(),
            'color' => $schema->string()->description('Nova cor da categoria financeira (hex, ex: #FF0000)')->nullable(),
            'operation_type' => $schema->string()->description('Novo tipo de operação (entry ou exit)')->nullable(),
            'cost_center_id' => $schema->integer()->description('Novo ID do centro de custo associado')->nullable(),
        ];
    }
}
