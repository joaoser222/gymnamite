<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\PlanCategories\UpdatePlanCategoryAction;
use App\DTOs\PlanCategories\UpdatePlanCategoryDTO;
use App\Models\PlanCategory;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-plan-category')]
#[Description('Atualiza uma categoria de planos existente')]
#[IsIdempotent(true)]
class UpdatePlanCategoryTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdatePlanCategoryAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
        ]);

        $planCategory = PlanCategory::find($validated['id']);

        if (! $planCategory) {
            return Response::error('Categoria de planos não encontrada.');
        }

        $dto = UpdatePlanCategoryDTO::from(array_merge(
            ['id' => $planCategory->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('plan_categories.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da categoria de planos')->required(),
            'name' => $schema->string()->description('Novo nome da categoria de planos')->nullable(),
        ];
    }
}
