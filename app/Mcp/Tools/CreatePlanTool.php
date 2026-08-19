<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Plans\CreatePlanAction;
use App\DTOs\Plans\CreatePlanDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-plan')]
#[Description('Cria um novo plano no sistema')]
#[IsIdempotent(false)]
class CreatePlanTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreatePlanAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_category_id' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
            'modality_quantity' => 'required|integer|min:1',
            'tiers' => 'required|array|min:1',
            'plan_modalities' => 'nullable|array',
        ]);

        $dto = CreatePlanDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'description' => $result->data->description,
            'visibility' => $result->data->visibility,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('plans.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome do plano')->required(),
            'plan_category_id' => $schema->integer()->description('ID da categoria do plano')->required(),
            'description' => $schema->string()->description('Descrição do plano')->nullable(),
            'modality_quantity' => $schema->integer()->description('Quantidade de modalidades permitidas')->required(),
            'tiers' => $schema->array()->description('Tiers/preços do plano')->required(),
            'plan_modalities' => $schema->array()->description('Modalidades vinculadas ao plano')->nullable(),
        ];
    }
}
