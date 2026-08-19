<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Modalities\CreateModalityAction;
use App\DTOs\Modalities\CreateModalityDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-modality')]
#[Description('Cria uma nova modalidade no sistema')]
#[IsIdempotent(false)]
class CreateModalityTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateModalityAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = CreateModalityDTO::from($validated);
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
        return auth()->user()?->can('modalities.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome da modalidade')->required(),
        ];
    }
}
