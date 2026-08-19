<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\PlanCategories\CreatePlanCategoryAction;
use App\DTOs\PlanCategories\CreatePlanCategoryDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-plan-category')]
#[Description('Cria uma nova categoria de planos')]
#[IsIdempotent(false)]
class CreatePlanCategoryTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreatePlanCategoryAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = CreatePlanCategoryDTO::from($validated);
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
        return auth()->user()?->can('plan_categories.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome da categoria de planos')->required(),
        ];
    }
}
